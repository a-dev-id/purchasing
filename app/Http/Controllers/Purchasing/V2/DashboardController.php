<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = PurchaseRequest::query()
            ->with([
                'items.vendorOffers',
            ])
            ->withCount('items')
            ->where('status', '!=', 'draft')
            ->latest('updated_at');

        if (($user->role ?? null) === 'requester') {
            $query->where(function ($query) use ($user) {
                $query->where('requested_by', $user->id);

                if (! empty($user->department_name)) {
                    $query->orWhere('department_name', $user->department_name);
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($query) use ($search) {
                        $query
                            ->where('item_name', 'like', "%{$search}%")
                            ->orWhere('specification', 'like', "%{$search}%")
                            ->orWhere('unit', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('department')) {
            $query->where('department_name', $request->string('department')->toString());
        }

        $purchaseRequests = $query
            ->limit(30)
            ->get();

        $statuses = PurchaseRequest::query()
            ->where('status', '!=', 'draft')
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $departments = PurchaseRequest::query()
            ->whereNotNull('department_name')
            ->select('department_name')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        return view('purchasing.v2.dashboard', [
            'user' => $user,
            'purchaseRequests' => $purchaseRequests,
            'statuses' => $statuses,
            'departments' => $departments,
            'isFinancialController' => $this->isFinancialControllerUser(),
            'canUpdateActionStatus' => $this->canUpdateActionStatusUser(),
        ]);
    }

    public function updateFcStatus(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($this->canUpdateActionStatusUser(), 403);

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:done,on_progress,on_shipping,cancelled,pending,waiting_for_payment,purchase',
            ],
            'fc_remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $fromStatus = $purchaseRequest->status;
            $toStatus = $validated['status'];

            $updateData = [
                'status' => $toStatus,
                'fc_remarks' => $validated['fc_remarks'] ?? null,
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ];

            if (Schema::hasColumn('purchase_requests', 'fc_action_updated_at')) {
                $updateData['fc_action_updated_at'] = now();
            }

            if ($toStatus === 'done' && Schema::hasColumn('purchase_requests', 'received_at')) {
                $updateData['received_at'] = now();
            }

            if ($toStatus === 'cancelled' && Schema::hasColumn('purchase_requests', 'cancelled_at')) {
                $updateData['cancelled_at'] = now();
            }

            $purchaseRequest->update($updateData);

            $this->writeLitePrLog(
                $purchaseRequest,
                'pr_status_update',
                $fromStatus,
                $toStatus,
                $this->buildStatusLogMessage($fromStatus, $toStatus, $validated['fc_remarks'] ?? null)
            );
        });

        return redirect()
            ->route('purchasing.v2.dashboard', request()->only(['search', 'status', 'department']))
            ->with('success', 'PR status and remarks have been updated.');
    }

    private function buildStatusLogMessage(
        ?string $fromStatus,
        string $toStatus,
        ?string $remarks = null
    ): string {
        $fromLabel = $fromStatus
            ? strtoupper(str_replace('_', ' ', $fromStatus))
            : '-';

        $toLabel = strtoupper(str_replace('_', ' ', $toStatus));

        $message = "PR status changed from {$fromLabel} to {$toLabel}.";

        if (! empty($remarks)) {
            $message .= ' Remarks: ' . $remarks;
        }

        return $message;
    }

    private function isFinancialControllerUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        $role = str_replace(['-', '_'], ' ', $role);

        return in_array($role, [
            'admin',
            'financial controller',
            'financial-controller',
            'financial_controller',
            'fc',
        ], true);
    }

    private function isPurchasingUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        $role = str_replace(['-', '_'], ' ', $role);

        return in_array($role, [
            'admin',
            'purchasing',
            'purchase',
        ], true);
    }

    private function canUpdateActionStatusUser(): bool
    {
        return $this->isFinancialControllerUser() || $this->isPurchasingUser();
    }

    private function writeLitePrLog(
        PurchaseRequest $purchaseRequest,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $message = null
    ): void {
        if (! Schema::hasTable('purchase_request_logs')) {
            return;
        }

        $now = now();
        $user = Auth::user();

        $data = [];

        if (Schema::hasColumn('purchase_request_logs', 'purchase_request_id')) {
            $data['purchase_request_id'] = $purchaseRequest->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'user_id')) {
            $data['user_id'] = $user?->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'role')) {
            $data['role'] = $user?->role;
        }

        if (Schema::hasColumn('purchase_request_logs', 'action')) {
            $data['action'] = $action;
        }

        if (Schema::hasColumn('purchase_request_logs', 'from_status')) {
            $data['from_status'] = $fromStatus;
        }

        if (Schema::hasColumn('purchase_request_logs', 'to_status')) {
            $data['to_status'] = $toStatus;
        }

        if (Schema::hasColumn('purchase_request_logs', 'message')) {
            $data['message'] = $message;
        } elseif (Schema::hasColumn('purchase_request_logs', 'notes')) {
            $data['notes'] = $message;
        } elseif (Schema::hasColumn('purchase_request_logs', 'remarks')) {
            $data['remarks'] = $message;
        }

        if (Schema::hasColumn('purchase_request_logs', 'acted_at')) {
            $data['acted_at'] = $now;
        }

        if (Schema::hasColumn('purchase_request_logs', 'created_at')) {
            $data['created_at'] = $now;
        }

        if (Schema::hasColumn('purchase_request_logs', 'updated_at')) {
            $data['updated_at'] = $now;
        }

        if (! empty($data)) {
            DB::table('purchase_request_logs')->insert($data);
        }
    }
}
