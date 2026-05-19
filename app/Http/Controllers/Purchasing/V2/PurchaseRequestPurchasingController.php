<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseRequestPurchasingController extends Controller
{
    public function saveVendorOffers(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        abort_unless(in_array($normalizedRole, ['admin', 'purchasing'], true), 403);

        $allowedStatuses = [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'on_hold_by_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'Vendor bids can only be edited while the request is on Purchasing.',
                ]);
        }

        $validated = $request->validate([
            'vendor_offers' => ['nullable', 'array'],

            'vendor_offers.*' => ['nullable', 'array'],
            'vendor_offers.*.*' => ['nullable', 'array'],

            'vendor_offers.*.*.vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'vendor_offers.*.*.vendor_name' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.category' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.contact_person' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.phone' => ['nullable', 'string', 'max:191'],
            'vendor_offers.*.*.email' => ['nullable', 'email', 'max:191'],
            'vendor_offers.*.*.currency' => ['nullable', 'string', 'max:10'],
            'vendor_offers.*.*.offer_total' => ['nullable'],
            'vendor_offers.*.*.offer_notes' => ['nullable', 'string'],
        ]);

        $vendorOfferRows = $validated['vendor_offers'] ?? [];

        $purchaseRequest->load('items.vendorOffers');

        DB::transaction(function () use ($purchaseRequest, $vendorOfferRows) {
            foreach ($purchaseRequest->items as $requestItem) {
                $itemOfferRows = $vendorOfferRows[$requestItem->id] ?? [];

                for ($rank = 1; $rank <= 3; $rank++) {
                    $offerRow = $itemOfferRows[$rank] ?? [];

                    $vendorId = $offerRow['vendor_id'] ?? null;
                    $vendorName = trim((string) ($offerRow['vendor_name'] ?? ''));
                    $offerNotes = trim((string) ($offerRow['offer_notes'] ?? ''));
                    $offerTotal = $this->normalizeMoney($offerRow['offer_total'] ?? null);

                    $hasAnyValue = $vendorId || $vendorName !== '' || $offerNotes !== '' || $offerTotal > 0;

                    if (! $hasAnyValue) {
                        $requestItem->vendorOffers()
                            ->where('offer_rank', $rank)
                            ->delete();

                        continue;
                    }

                    $vendor = null;

                    if ($vendorId) {
                        $vendor = Vendor::query()->find($vendorId);
                    }

                    if (! $vendor && $vendorName !== '') {
                        $vendor = Vendor::query()
                            ->whereRaw('LOWER(name) = ?', [strtolower($vendorName)])
                            ->first();

                        if (! $vendor) {
                            $vendor = Vendor::create([
                                'name' => $vendorName,
                                'category' => $offerRow['category'] ?? null,
                                'contact_person' => $offerRow['contact_person'] ?? null,
                                'phone' => $offerRow['phone'] ?? null,
                                'email' => $offerRow['email'] ?? null,
                                'notes' => $offerNotes ?: null,
                                'is_active' => true,
                            ]);
                        } else {
                            $vendor->update([
                                'category' => $vendor->category ?: ($offerRow['category'] ?? null),
                                'contact_person' => $vendor->contact_person ?: ($offerRow['contact_person'] ?? null),
                                'phone' => $vendor->phone ?: ($offerRow['phone'] ?? null),
                                'email' => $vendor->email ?: ($offerRow['email'] ?? null),
                                'notes' => $vendor->notes ?: ($offerNotes ?: null),
                                'is_active' => true,
                            ]);
                        }
                    }

                    $finalVendorName = $vendor?->name ?: $vendorName;

                    $requestItem->vendorOffers()->updateOrCreate(
                        [
                            'purchase_request_item_id' => $requestItem->id,
                            'offer_rank' => $rank,
                        ],
                        [
                            'vendor_id' => $vendor?->id,
                            'vendor_name' => $finalVendorName,
                            'category' => $offerRow['category'] ?? $vendor?->category,
                            'contact_person' => $offerRow['contact_person'] ?? $vendor?->contact_person,
                            'phone' => $offerRow['phone'] ?? $vendor?->phone,
                            'email' => $offerRow['email'] ?? $vendor?->email,
                            'currency' => $offerRow['currency'] ?? 'IDR',
                            'offer_total' => $offerTotal,
                            'offer_notes' => $offerNotes ?: null,
                        ]
                    );
                }

                $this->updateItemPriceFromVendorBids($requestItem, $itemOfferRows);
            }

            $purchaseRequest->update([
                'last_activity_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Vendor bids have been saved successfully.');
    }

    public function submitToAccounting(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        abort_unless(in_array($normalizedRole, ['admin', 'purchasing'], true), 403);

        $allowedStatuses = [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'on_hold_by_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'This purchase request cannot be submitted to Cost Control from the current status.',
                ]);
        }

        $purchaseRequest->load('items.vendorOffers');

        $itemsWithoutBid = $purchaseRequest->items->filter(function ($item) {
            return ! $item->vendorOffers->contains(function ($offer) {
                return filled($offer->vendor_name) && (float) ($offer->offer_total ?? 0) > 0;
            });
        });

        if ($itemsWithoutBid->isNotEmpty()) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'vendor_offers' => 'Please add at least one vendor and price for every item before submitting to Cost Control.',
                ]);
        }

        $fromStatus = $purchaseRequest->status;
        $toStatus = 'submitted_to_accounting';

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $toStatus, $user) {
            $purchaseRequest->update([
                'status' => $toStatus,
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'purchasing_submit_to_accounting',
                $fromStatus,
                $toStatus,
                'Purchase request submitted to Cost Control.',
                $user
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been submitted to Cost Control.');
    }

    public function returnToRequester(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        abort_unless(in_array($normalizedRole, [
            'admin',
            'purchasing',
            'purchase',
            'purchasing staff',
        ], true), 403);

        $allowedStatuses = [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'on_hold_by_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'This purchase request cannot be returned to requester from the current status.',
                ]);
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $message = trim((string) ($validated['remark'] ?? $validated['message'] ?? ''));

        if ($message === '') {
            return back()
                ->withErrors([
                    'remark' => 'Remark / Message is required.',
                    'message' => 'Remark / Message is required.',
                ])
                ->withInput();
        }

        $fromStatus = $purchaseRequest->status;
        $toStatus = 'revision_from_purchasing';

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $toStatus, $message, $user) {
            $purchaseRequest->update([
                'status' => $toStatus,
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'purchasing_return_to_requester',
                $fromStatus,
                $toStatus,
                $message,
                $user
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been returned to requester.');
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        abort_unless(in_array($normalizedRole, [
            'admin',
            'purchasing',
            'purchase',
            'purchasing staff',
        ], true), 403);

        $allowedStatuses = [
            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'on_hold_by_gm',
        ];

        if (! in_array($purchaseRequest->status, $allowedStatuses, true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors([
                    'status' => 'This purchase request cannot be rejected from the current status.',
                ]);
        }

        $validated = $request->validate([
            'remark' => ['nullable', 'string', 'max:2000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $message = trim((string) ($validated['remark'] ?? $validated['message'] ?? ''));

        if ($message === '') {
            return back()
                ->withErrors([
                    'remark' => 'Rejection message is required.',
                    'message' => 'Rejection message is required.',
                ])
                ->withInput();
        }

        $fromStatus = $purchaseRequest->status;
        $toStatus = 'rejected';

        DB::transaction(function () use ($purchaseRequest, $fromStatus, $toStatus, $message, $user) {
            $updateData = [
                'status' => $toStatus,
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ];

            if (Schema::hasColumn('purchase_requests', 'cancelled_at')) {
                $updateData['cancelled_at'] = now();
            }

            $purchaseRequest->update($updateData);

            $this->writeLitePrLog(
                $purchaseRequest,
                'purchasing_reject',
                $fromStatus,
                $toStatus,
                $message,
                $user
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been rejected.');
    }

    private function updateItemPriceFromVendorBids($requestItem, array $itemOfferRows): void
    {
        $validPrices = [];

        foreach ([1, 2, 3] as $rank) {
            $offerRow = $itemOfferRows[$rank] ?? [];

            $price = $this->normalizeMoney($offerRow['offer_total'] ?? null);

            if ($price > 0) {
                $validPrices[] = [
                    'price' => $price,
                    'currency' => $offerRow['currency'] ?? 'IDR',
                ];
            }
        }

        if (empty($validPrices)) {
            return;
        }

        usort($validPrices, function ($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        $lowestBid = $validPrices[0];

        $lowestUnitPrice = (float) $lowestBid['price'];
        $currency = $lowestBid['currency'] ?: 'IDR';

        if (! empty($requestItem->item_id)) {
            $item = Item::query()->find($requestItem->item_id);

            if ($item) {
                $item->updateLastPriceFromPurchasing($lowestUnitPrice, $currency);
            }
        }
    }

    private function normalizeMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9,\.]/', '', (string) $value);

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');

            if ($lastComma > $lastDot) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }

            return (float) $clean;
        }

        if (str_contains($clean, ',')) {
            $parts = explode(',', $clean);

            if (count($parts) > 2) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }

            return (float) $clean;
        }

        if (substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        }

        return (float) ($clean ?: 0);
    }

    private function writeLitePrLog(
        PurchaseRequest $purchaseRequest,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $message,
        $user = null
    ): void {
        if (! Schema::hasTable('purchase_request_logs')) {
            return;
        }

        $now = now();

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
