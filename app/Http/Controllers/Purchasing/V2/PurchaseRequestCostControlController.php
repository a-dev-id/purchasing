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

    private function isCostControlUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        $role = str_replace(['-', '_'], ' ', $role);

        return in_array($role, [
            'admin',
            'accounting',
            'cost control',
            'cost controller',
        ], true);
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
