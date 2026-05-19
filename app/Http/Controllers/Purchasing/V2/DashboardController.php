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

        $this->attachDashboardRemarks($purchaseRequests);

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

    private function attachDashboardRemarks($purchaseRequests): void
    {
        if ($purchaseRequests->isEmpty()) {
            return;
        }

        $returnedToRequesterStatuses = [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];

        $rejectedStatuses = [
            'rejected',
        ];

        $fcRemarkStatuses = [
            'done',
            'cancelled',
            'pending',
            'purchase',
            'on_progress',
            'on_shipping',
            'waiting_for_payment',
            'waiting_payment_by_fc',
            'paid_to_vendor_by_fc',
            'item_arrived_by_fc',
        ];

        /*
        |--------------------------------------------------------------------------
        | Load dashboard remarks only for statuses that should display remarks.
        |--------------------------------------------------------------------------
        | 1. Returned to requester: show latest return message.
        | 2. Rejected: show latest rejection message.
        | 3. FC action statuses: show FC remarks.
        | Normal submitted/on-purchasing PRs must show "-".
        */
        $logRemarkPurchaseRequestIds = $purchaseRequests
            ->filter(function ($purchaseRequest) use ($returnedToRequesterStatuses, $rejectedStatuses) {
                return in_array($purchaseRequest->status, $returnedToRequesterStatuses, true)
                    || in_array($purchaseRequest->status, $rejectedStatuses, true);
            })
            ->pluck('id')
            ->filter()
            ->values();

        $latestLogRemarks = collect();

        if ($logRemarkPurchaseRequestIds->isNotEmpty() && Schema::hasTable('purchase_request_logs')) {
            $messageColumn = null;

            foreach (['message', 'remarks', 'notes'] as $column) {
                if (Schema::hasColumn('purchase_request_logs', $column)) {
                    $messageColumn = $column;
                    break;
                }
            }

            if ($messageColumn && Schema::hasColumn('purchase_request_logs', 'purchase_request_id')) {
                $orderColumn = 'id';

                if (Schema::hasColumn('purchase_request_logs', 'acted_at')) {
                    $orderColumn = 'acted_at';
                } elseif (Schema::hasColumn('purchase_request_logs', 'created_at')) {
                    $orderColumn = 'created_at';
                }

                $logs = DB::table('purchase_request_logs')
                    ->whereIn('purchase_request_id', $logRemarkPurchaseRequestIds)
                    ->whereNotNull($messageColumn)
                    ->where($messageColumn, '!=', '')
                    ->when(Schema::hasColumn('purchase_request_logs', 'action'), function ($query) {
                        $query->whereIn('action', [
                            'purchasing_return_to_requester',
                            'gm_send_back_to_requester',
                            'gm_send_back_to_purchasing',
                            'purchasing_reject',
                            'gm_reject',
                        ]);
                    })
                    ->orderBy('purchase_request_id')
                    ->orderByDesc($orderColumn)
                    ->orderByDesc('id')
                    ->get();

                $latestLogRemarks = $logs
                    ->groupBy('purchase_request_id')
                    ->map(function ($rows) use ($messageColumn) {
                        return trim((string) ($rows->first()->{$messageColumn} ?? ''));
                    });
            }
        }

        foreach ($purchaseRequests as $purchaseRequest) {
            $isReturnedToRequester = in_array($purchaseRequest->status, $returnedToRequesterStatuses, true);
            $isRejected = in_array($purchaseRequest->status, $rejectedStatuses, true);
            $isFcRemarkStatus = in_array($purchaseRequest->status, $fcRemarkStatuses, true);

            $latestLogRemark = ($isReturnedToRequester || $isRejected)
                ? trim((string) ($latestLogRemarks[$purchaseRequest->id] ?? ''))
                : '';

            $fcRemark = $isFcRemarkStatus
                ? trim((string) ($purchaseRequest->fc_remarks ?? ''))
                : '';

            $dashboardRemark = $latestLogRemark !== ''
                ? $latestLogRemark
                : $fcRemark;

            $purchaseRequest->setAttribute('latest_action_remark', $latestLogRemark ?: null);
            $purchaseRequest->setAttribute('dashboard_remark', $dashboardRemark ?: null);
            $purchaseRequest->setAttribute('dashboard_remarks', $dashboardRemark ?: null);
            $purchaseRequest->setAttribute('remarks', $dashboardRemark ?: null);
            $purchaseRequest->setAttribute('fc_remarks', $dashboardRemark ?: null);
        }
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
