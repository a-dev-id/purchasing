<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItemVendorOffer;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestPurchasingController extends Controller
{
    public function saveVendorOffers(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        abort_unless(in_array($role, ['purchasing', 'admin'], true), 403);

        $allowedStatuses = [
            'submitted',
            'on_hold_by_gm',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'Vendor bids can only be edited while the PR is waiting for Purchasing review.',
                ]);
        }

        $validated = $request->validate([
            'vendor_offers' => ['nullable', 'array'],

            'vendor_offers.*.*.vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'vendor_offers.*.*.vendor_name' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.category' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.contact_person' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.phone' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.email' => ['nullable', 'email', 'max:191'],
            'vendor_offers.*.*.offer_total' => ['nullable', 'numeric', 'min:0'],
            'vendor_offers.*.*.currency' => ['nullable', 'string', 'in:IDR'],
            'vendor_offers.*.*.offer_notes' => ['nullable', 'string'],
        ]);

        $purchaseRequest->load('items');

        foreach ($purchaseRequest->items as $item) {
            $offers = $validated['vendor_offers'][$item->id] ?? [];

            for ($rank = 1; $rank <= 3; $rank++) {
                $offer = $offers[$rank] ?? [];

                $vendor = null;

                if (! empty($offer['vendor_id'])) {
                    $vendor = Vendor::query()->find($offer['vendor_id']);
                }

                $vendorName = $vendor?->name ?: ($offer['vendor_name'] ?? null);

                $hasVendorData = ! empty($vendorName)
                    || ! empty($offer['offer_total'])
                    || ! empty($offer['offer_notes']);

                $existingOffer = PurchaseRequestItemVendorOffer::query()
                    ->where('purchase_request_item_id', $item->id)
                    ->where('offer_rank', $rank)
                    ->first();

                if (! $hasVendorData) {
                    if ($existingOffer) {
                        $existingOffer->delete();
                    }

                    continue;
                }

                PurchaseRequestItemVendorOffer::updateOrCreate(
                    [
                        'purchase_request_item_id' => $item->id,
                        'offer_rank' => $rank,
                    ],
                    [
                        'vendor_id' => $vendor?->id,
                        'vendor_name' => $vendorName,
                        'category' => $vendor?->category ?: ($offer['category'] ?? null),
                        'contact_person' => $vendor?->contact_person ?: ($offer['contact_person'] ?? null),
                        'phone' => $vendor?->phone ?: ($offer['phone'] ?? null),
                        'email' => $vendor?->email ?: ($offer['email'] ?? null),
                        'offer_total' => $offer['offer_total'] ?? 0,
                        'currency' => 'IDR',
                        'lead_time_days' => null,
                        'offer_notes' => $offer['offer_notes'] ?? null,
                    ]
                );
            }
        }

        $purchaseRequest->update([
            'last_activity_at' => now(),
        ]);

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Vendor bids have been saved successfully.');
    }

    public function submitToAccounting(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        abort_unless(in_array($role, ['purchasing', 'admin'], true), 403);

        $allowedStatuses = [
            'submitted',
            'on_hold_by_gm',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'This purchase request is not waiting for Purchasing review.',
                ]);
        }

        $purchaseRequest->load('items.vendorOffers');

        foreach ($purchaseRequest->items as $item) {
            $validOffersCount = $item->vendorOffers
                ->filter(fn($offer) => ! empty($offer->vendor_name) && (float) $offer->offer_total > 0)
                ->count();

            if ($validOffersCount < 3) {
                return redirect()
                    ->route('purchasing.v2.requests.show', $purchaseRequest)
                    ->withErrors([
                        'vendor_offers' => 'Please fill Bid 1, Bid 2, and Bid 3 for every item before submitting to Cost Control.',
                    ]);
            }
        }

        $purchaseRequest->update([
            'status' => 'submitted_to_accounting',
            'current_status_at' => now(),
            'last_activity_at' => now(),
        ]);

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been submitted to Cost Control.');
    }
}
