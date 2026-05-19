<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseRequestCostControlController extends Controller
{
    public function saveSelectedVendors(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isCostControlUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_accounting') {
            return back()->with('error', 'Vendor selection is only available when the request is submitted to Cost Control.');
        }

        $validated = $request->validate([
            'selected_offers' => ['required', 'array'],
            'selected_offers.*' => ['required', 'integer', 'exists:purchase_request_item_vendor_offers,id'],
        ]);

        $purchaseRequest->load([
            'items.vendorOffers',
        ]);

        DB::transaction(function () use ($purchaseRequest, $validated) {
            foreach ($purchaseRequest->items as $item) {
                $selectedOfferId = (int) ($validated['selected_offers'][$item->id] ?? 0);

                if (! $selectedOfferId) {
                    throw ValidationException::withMessages([
                        "selected_offers.{$item->id}" => "Please select one vendor for {$item->item_name}.",
                    ]);
                }

                $selectedOffer = $item->vendorOffers->firstWhere('id', $selectedOfferId);

                if (! $selectedOffer) {
                    throw ValidationException::withMessages([
                        "selected_offers.{$item->id}" => "Invalid vendor selection for {$item->item_name}.",
                    ]);
                }

                $item->vendorOffers()->update([
                    'is_selected_by_accounting' => false,
                ]);

                $selectedOffer->update([
                    'is_selected_by_accounting' => true,
                ]);
            }

            $purchaseRequest->update([
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'selected_vendor_by_cost_control',
                $purchaseRequest->status,
                $purchaseRequest->status,
                'Cost Control selected winning vendors for the request items.'
            );
        });

        return back()->with('success', 'Selected vendors have been saved.');
    }

    public function submitToGm(PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isCostControlUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_accounting') {
            return back()->with('error', 'This request cannot be submitted to GM from the current status.');
        }

        $purchaseRequest->load([
            'items.vendorOffers',
        ]);

        foreach ($purchaseRequest->items as $item) {
            $hasSelectedVendor = $item->vendorOffers->contains('is_selected_by_accounting', true);

            if (! $hasSelectedVendor) {
                return back()->with('error', "Please select one vendor for {$item->item_name} before submitting to GM.");
            }
        }

        $fromStatus = $purchaseRequest->status;

        DB::transaction(function () use ($purchaseRequest, $fromStatus) {
            $purchaseRequest->update([
                'status' => 'submitted_to_gm',
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'submitted_to_gm',
                $fromStatus,
                'submitted_to_gm',
                'Cost Control submitted the purchase request to GM.'
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been submitted to GM.');
    }

    public function returnToPurchasingFromAccounting(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isCostControlUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_accounting') {
            return back()->with('error', 'This purchase request is not waiting for Cost Control.');
        }

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:5000'],
        ]);

        $fromStatus = $purchaseRequest->status;
        $remark = $validated['remark'];

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $remark) {
            $purchaseRequest->update([
                'status' => 'revision_to_purchasing_from_accounting',
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'returned_to_purchasing_from_accounting',
                $fromStatus,
                'revision_to_purchasing_from_accounting',
                $remark
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been sent back to Purchasing.');
    }

    public function returnToRequesterFromAccounting(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isCostControlUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_accounting') {
            return back()->with('error', 'This purchase request is not waiting for Cost Control.');
        }

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:5000'],
        ]);

        $fromStatus = $purchaseRequest->status;
        $remark = $validated['remark'];

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $remark) {
            $purchaseRequest->update([
                'status' => 'revision_to_requester_from_accounting',
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'returned_to_requester_from_accounting',
                $fromStatus,
                'revision_to_requester_from_accounting',
                $remark
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been sent back to Requester.');
    }

    public function rejectFromAccounting(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isCostControlUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_accounting') {
            return back()->with('error', 'This purchase request is not waiting for Cost Control.');
        }

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:5000'],
        ]);

        $fromStatus = $purchaseRequest->status;
        $remark = $validated['remark'];

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $remark) {
            $purchaseRequest->update([
                'status' => 'rejected',
                'current_status_at' => now(),
                'last_activity_at' => now(),
                'cancelled_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'rejected_by_accounting',
                $fromStatus,
                'rejected',
                $remark
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been rejected.');
    }

    private function isCostControlUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $allowedRoles = [
            'admin',
            'super admin',
            'accounting',
            'accountant',
            'cost control',
            'cost controller',
            'costcontrol',
        ];

        foreach ($this->getUserRoleValues($user) as $roleValue) {
            if (in_array($this->normalizeRole($roleValue), $allowedRoles, true)) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($allowedRoles)) {
                return true;
            }
        }

        return false;
    }

    private function getUserRoleValues($user): array
    {
        $roles = [];

        foreach (
            [
                'role',
                'role_name',
                'user_role',
                'account_type',
                'type',
                'position',
                'department',
                'department_name',
            ] as $field
        ) {
            if (! empty($user->{$field})) {
                $roles[] = (string) $user->{$field};
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            foreach ($user->getRoleNames() as $roleName) {
                $roles[] = (string) $roleName;
            }
        }

        if (isset($user->roles)) {
            foreach ($user->roles as $role) {
                if (is_string($role)) {
                    $roles[] = $role;
                    continue;
                }

                foreach (['name', 'role', 'role_name', 'title'] as $field) {
                    if (! empty($role->{$field})) {
                        $roles[] = (string) $role->{$field};
                    }
                }
            }
        }

        return array_values(array_filter(array_unique($roles)));
    }

    private function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));
        $role = str_replace(['-', '_'], ' ', $role);
        $role = preg_replace('/\s+/', ' ', $role);

        return trim($role);
    }

    private function getUserRoleForLog($user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (
            [
                'role',
                'role_name',
                'user_role',
                'account_type',
                'type',
                'position',
                'department_name',
            ] as $field
        ) {
            if (! empty($user->{$field})) {
                return (string) $user->{$field};
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first();
        }

        return null;
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

        if (Schema::hasColumn('purchase_request_logs', 'user_name')) {
            $data['user_name'] = $user?->name;
        }

        if (Schema::hasColumn('purchase_request_logs', 'role')) {
            $data['role'] = $this->getUserRoleForLog($user);
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

        foreach (['remark', 'remarks', 'message', 'notes'] as $column) {
            if (Schema::hasColumn('purchase_request_logs', $column)) {
                $data[$column] = $message;
            }
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
