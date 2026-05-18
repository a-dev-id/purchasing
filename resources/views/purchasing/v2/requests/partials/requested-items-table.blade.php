@php
$userRole = strtolower((string) (auth()->user()->role ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$isGmUser = in_array($normalizedRole, [
'admin',
'gm',
'general manager',
], true);

$showBidColumns = ! in_array($normalizedRole, [
'gm',
'general manager',
], true);

$canGmApproveItems = $isGmUser && $purchaseRequest->status === 'submitted_to_gm';

$purchasingEditableStatuses = [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
];

$canEditVendorOffers = in_array($normalizedRole, ['purchasing', 'admin'], true)
&& in_array($purchaseRequest->status, $purchasingEditableStatuses, true);

$isCostControlUser = in_array($normalizedRole, [
'admin',
'accounting',
'cost control',
'cost controller',
], true);

$canSelectVendor = $isCostControlUser
&& $purchaseRequest->status === 'submitted_to_accounting';

$allItemsHaveSelectedVendor = $purchaseRequest->items->every(function ($item) {
return $item->vendorOffers->contains('is_selected_by_accounting', true);
});

$grandTotal = $purchaseRequest->items->sum(function ($item) {
$qty = (float) ($item->qty ?? 0);

$selectedOffer = $item->vendorOffers->firstWhere('is_selected_by_accounting', true);

if (! $selectedOffer) {
return 0;
}

return $qty * (float) ($selectedOffer->offer_total ?? 0);
});
@endphp

<div class="mb-2">
    <h3 class="text-base font-bold text-gray-900">
        Requested Items
    </h3>
</div>

@if ($canEditVendorOffers)
<form method="POST" action="{{ route('purchasing.v2.requests.vendor-offers.save', $purchaseRequest) }}">
    @csrf
    @elseif ($canSelectVendor)
    <form method="POST" action="{{ route('purchasing.v2.requests.save-selected-vendors', $purchaseRequest) }}">
        @csrf
        @elseif ($canGmApproveItems)
        <form method="POST" action="{{ route('purchasing.v2.requests.gm-approve-items', $purchaseRequest) }}">
            @csrf
            @endif

            <div class="bg-white border border-gray-400 overflow-x-auto mb-4">
                <table class="w-full {{ $showBidColumns ? 'min-w-[2250px]' : 'min-w-[1500px]' }} border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[50px]">
                                No
                            </th>

                            @if ($canGmApproveItems)
                            <th class="border border-gray-400 px-3 py-2 text-center whitespace-nowrap w-[230px]">
                                Approve
                            </th>
                            @endif

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">
                                Item Name
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[150px]">
                                Photos
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[80px]">
                                Qty
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[90px]">
                                Unit
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[220px]">
                                Selected Vendor
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[160px]">
                                Price / Unit
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-right whitespace-nowrap w-[170px]">
                                Total Price
                            </th>

                            @if ($showBidColumns)
                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
                                Bid 1
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
                                Bid 2
                            </th>

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[260px]">
                                Bid 3
                            </th>
                            @endif

                            <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">
                                Specification
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($purchaseRequest->items as $requestItem)
                        @php
                        $qty = (float) ($requestItem->qty ?? 0);
                        $photos = $requestItem->item?->photos ?? collect();
                        $offers = $requestItem->vendorOffers->keyBy('offer_rank');

                        $selectedOffer = $requestItem->vendorOffers->firstWhere('is_selected_by_accounting', true);
                        $selectedUnitPrice = $selectedOffer ? (float) ($selectedOffer->offer_total ?? 0) : 0;
                        $selectedTotalPrice = $qty * $selectedUnitPrice;

                        $selectedColumnClass = in_array($normalizedRole, ['gm', 'general manager'], true) ? '' : 'bg-green-50';

                        $hasOldInput = old('_token') !== null;
                        $oldApprovedItems = old('approved_items', []);

                        $isApprovedChecked = $hasOldInput
                        ? in_array((string) $requestItem->id, array_map('strval', (array) $oldApprovedItems), true)
                        : true;

                        $storedReason = (string) ($requestItem->gm_not_approved_reason ?? '');
                        $storedReasonSelectValue = '';
                        $storedReasonDetail = '';

                        if ($storedReason === 'Canceled') {
                        $storedReasonSelectValue = 'Canceled';
                        } elseif (str_starts_with($storedReason, 'Reason: ')) {
                        $storedReasonSelectValue = 'Reason';
                        $storedReasonDetail = trim(substr($storedReason, 8));
                        } elseif (str_starts_with($storedReason, 'Other: ')) {
                        $storedReasonSelectValue = 'Reason';
                        $storedReasonDetail = trim(substr($storedReason, 7));
                        } elseif (filled($storedReason)) {
                        $storedReasonSelectValue = 'Reason';
                        $storedReasonDetail = $storedReason;
                        }

                        $selectedNotApprovedReason = old(
                        "not_approved_reasons.{$requestItem->id}",
                        $storedReasonSelectValue
                        );

                        $selectedReasonDetail = old(
                        "not_approved_reason_details.{$requestItem->id}",
                        $storedReasonDetail
                        );

                        $showReasonDetail = ! $isApprovedChecked && $selectedNotApprovedReason === 'Reason';
                        @endphp

                        <tr class="hover:bg-gray-50 align-top">
                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                                {{ $loop->iteration }}
                            </td>

                            @if ($canGmApproveItems)
                            <td class="border border-gray-300 px-3 py-2 text-center align-top">
                                <input type="checkbox" name="approved_items[]" value="{{ $requestItem->id }}" class="h-4 w-4 gm-approve-checkbox" data-item-id="{{ $requestItem->id }}" @checked($isApprovedChecked)>

                                <div class="{{ $isApprovedChecked ? 'hidden ' : '' }}mt-2 text-left gm-not-approved-reason-wrap" data-item-id="{{ $requestItem->id }}">
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                        Not approve reason
                                    </label>

                                    <select name="not_approved_reasons[{{ $requestItem->id }}]" class="w-full border border-gray-300 px-2 py-1 text-xs gm-not-approved-reason" data-item-id="{{ $requestItem->id }}" @disabled($isApprovedChecked) @required(! $isApprovedChecked)>
                                        <option value="">Select reason</option>

                                        <option value="Reason" @selected($selectedNotApprovedReason==='Reason' )>
                                            Reason
                                        </option>

                                        <option value="Canceled" @selected($selectedNotApprovedReason==='Canceled' )>
                                            Canceled
                                        </option>
                                    </select>

                                    <div class="{{ $showReasonDetail ? '' : 'hidden ' }}mt-2 gm-not-approved-reason-detail-wrap" data-item-id="{{ $requestItem->id }}">
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                            Reason detail
                                        </label>

                                        <textarea name="not_approved_reason_details[{{ $requestItem->id }}]" rows="3" class="w-full border border-gray-300 px-2 py-1 text-xs gm-not-approved-reason-detail" data-item-id="{{ $requestItem->id }}" placeholder="Input reason..." @disabled(! $showReasonDetail) @required($showReasonDetail)>{{ $selectedReasonDetail }}</textarea>
                                    </div>
                                </div>
                            </td>
                            @endif

                            <td class="border border-gray-300 px-3 py-2 font-semibold min-w-[260px]">
                                {{ $requestItem->item_name ?? $requestItem->item?->name ?? '-' }}
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                                @if ($photos->isNotEmpty())
                                <div class="flex gap-1">
                                    @foreach ($photos->take(2) as $photo)
                                    <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $requestItem->item_name }}" class="w-12 h-12 object-cover border border-gray-300 hover:opacity-80">
                                    </a>
                                    @endforeach

                                    @if ($photos->count() > 2)
                                    <div class="w-12 h-12 border border-gray-300 bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                                        +{{ $photos->count() - 2 }}
                                    </div>
                                    @endif
                                </div>
                                @else
                                <span class="text-xs text-gray-400">
                                    No Photo
                                </span>
                                @endif
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right">
                                {{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                                {{ $requestItem->unit ?? '-' }}
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap {{ $selectedColumnClass }}">
                                @if ($selectedOffer)
                                <div class="font-bold text-gray-900">
                                    {{ $selectedOffer->vendor_name ?? '-' }}
                                </div>

                                @unless (in_array($normalizedRole, ['gm', 'general manager'], true))
                                <div class="mt-1 inline-block border border-green-600 bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">
                                    Selected
                                </div>
                                @endunless
                                @else
                                <span class="text-xs text-red-500">
                                    No selected vendor
                                </span>
                                @endif
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right {{ $selectedColumnClass }}">
                                @if ($selectedOffer)
                                Rp {{ number_format($selectedUnitPrice, 0, ',', '.') }}
                                @else
                                -
                                @endif
                            </td>

                            <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right font-bold {{ $selectedColumnClass }}">
                                @if ($selectedOffer)
                                Rp {{ number_format($selectedTotalPrice, 0, ',', '.') }}
                                @else
                                -
                                @endif
                            </td>

                            @if ($showBidColumns)
                            @for ($rank = 1; $rank <= 3; $rank++) @php $offer=$offers->get($rank);
                                $prefix = 'vendor_offers.' . $requestItem->id . '.' . $rank;
                                $isSelected = (bool) ($offer?->is_selected_by_accounting ?? false);
                                @endphp

                                <td class="border border-gray-300 px-2 py-2 min-w-[260px] {{ $isSelected ? 'bg-green-50' : '' }}">
                                    @if ($canEditVendorOffers)
                                    <div class="relative vendor-field mb-2">
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                            Vendor
                                        </label>

                                        <input type="text" value="{{ old($prefix . '.vendor_name', $offer?->vendor_name) }}" class="vendor-search w-full border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Search vendor..." autocomplete="off">

                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][vendor_id]" value="{{ old($prefix . '.vendor_id', $offer?->vendor_id) }}" class="vendor-id-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][vendor_name]" value="{{ old($prefix . '.vendor_name', $offer?->vendor_name) }}" class="vendor-name-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][category]" value="{{ old($prefix . '.category', $offer?->category) }}" class="vendor-category-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][contact_person]" value="{{ old($prefix . '.contact_person', $offer?->contact_person) }}" class="vendor-contact-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][phone]" value="{{ old($prefix . '.phone', $offer?->phone) }}" class="vendor-phone-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][email]" value="{{ old($prefix . '.email', $offer?->email) }}" class="vendor-email-hidden">
                                        <input type="hidden" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][currency]" value="IDR">

                                        <div class="vendor-search-results hidden absolute z-50 left-0 right-0 top-[48px] max-h-52 overflow-y-auto bg-white border border-gray-400 shadow text-xs"></div>

                                        <div class="vendor-details text-[11px] text-gray-500 mt-1">
                                            @if ($offer?->phone || $offer?->email)
                                            {{ $offer?->phone }}

                                            @if ($offer?->phone && $offer?->email)
                                            |
                                            @endif

                                            {{ $offer?->email }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                            Price
                                        </label>

                                        <input type="text" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][offer_total]" value="{{ old($prefix . '.offer_total', $offer?->offer_total ? (int) $offer->offer_total : '') }}" min="0" step="1" class="w-full border border-gray-300 px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">
                                            Notes
                                        </label>

                                        <input type="text" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][offer_notes]" value="{{ old($prefix . '.offer_notes', $offer?->offer_notes) }}" class="w-full border border-gray-300 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-700">
                                    </div>
                                    @else
                                    @if ($offer)
                                    <div class="font-semibold">
                                        {{ $offer->vendor_name ?? '-' }}
                                    </div>

                                    <div class="text-xs text-gray-700 mt-1">
                                        Rp {{ number_format((float) $offer->offer_total, 0, ',', '.') }}
                                    </div>

                                    @if ($offer->offer_notes)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $offer->offer_notes }}
                                    </div>
                                    @endif

                                    @if ($canSelectVendor)
                                    <label class="mt-2 inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700">
                                        <input type="radio" name="selected_offers[{{ $requestItem->id }}]" value="{{ $offer->id }}" @checked($isSelected) required>

                                        Choose
                                    </label>
                                    @endif

                                    @if ($isSelected)
                                    <div class="mt-2 inline-block border border-green-600 bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">
                                        Selected
                                    </div>
                                    @endif
                                    @else
                                    <span class="text-xs text-gray-400">
                                        No Bid
                                    </span>
                                    @endif
                                    @endif
                                </td>
                                @endfor
                                @endif

                                <td class="border border-gray-300 px-3 py-2 min-w-[250px]">
                                    {{ strip_tags($requestItem->specification ?? '-') }}
                                </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($showBidColumns ? 12 : 9) + ($canGmApproveItems ? 1 : 0) }}" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                                No items found.
                            </td>
                        </tr>
                        @endforelse

                        <tr class="bg-gray-100">
                            <td colspan="{{ $canGmApproveItems ? 8 : 7 }}" class="border border-gray-400 px-3 py-3 text-right font-bold text-gray-900">
                                Grand Total
                            </td>

                            <td class="border border-gray-400 px-3 py-3 text-right font-bold text-gray-900">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </td>

                            <td colspan="{{ $showBidColumns ? 4 : 1 }}" class="border border-gray-400 px-3 py-3"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if ($canEditVendorOffers)
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="submit" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                    Save Bids
                </button>
            </div>

        </form>
        @elseif ($canSelectVendor)
        <div class="mb-4 flex flex-wrap gap-2">
            <button type="submit" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Save Selected Vendors
            </button>
        </div>

    </form>
    @elseif ($canGmApproveItems)
    <div class="mb-4 bg-white border border-gray-300 p-4">
        <div class="mb-3 text-sm font-bold text-gray-900">
            GM Approval
        </div>

        <input type="hidden" name="deferred_until" value="{{ old('deferred_until', $purchaseRequest->date_needed?->toDateString() ?? now()->addMonth()->toDateString()) }}">

        <button type="submit" onclick="return confirm('Approve selected items? Unticked Reason items will be moved to a new child PR. Unticked Canceled items will not be moved.')" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            Approve Selected Items
        </button>
    </div>

</form>
@endif

@if ($canGmApproveItems)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.gm-approve-checkbox');

        checkboxes.forEach(function (checkbox) {
            const itemId = checkbox.dataset.itemId;

            const reasonWrap = document.querySelector(
                '.gm-not-approved-reason-wrap[data-item-id="' + itemId + '"]'
            );

            const reasonSelect = document.querySelector(
                '.gm-not-approved-reason[data-item-id="' + itemId + '"]'
            );

            const reasonDetailWrap = document.querySelector(
                '.gm-not-approved-reason-detail-wrap[data-item-id="' + itemId + '"]'
            );

            const reasonDetailTextarea = document.querySelector(
                '.gm-not-approved-reason-detail[data-item-id="' + itemId + '"]'
            );

            function toggleReasonDetail() {
                if (!reasonSelect || !reasonDetailWrap || !reasonDetailTextarea) {
                    return;
                }

                if (reasonSelect.value === 'Reason' && !checkbox.checked) {
                    reasonDetailWrap.classList.remove('hidden');
                    reasonDetailTextarea.disabled = false;
                    reasonDetailTextarea.required = true;
                } else {
                    reasonDetailWrap.classList.add('hidden');
                    reasonDetailTextarea.value = '';
                    reasonDetailTextarea.disabled = true;
                    reasonDetailTextarea.required = false;
                }
            }

            function toggleReasonDropdown() {
                if (!reasonWrap || !reasonSelect) {
                    return;
                }

                if (checkbox.checked) {
                    reasonWrap.classList.add('hidden');
                    reasonSelect.value = '';
                    reasonSelect.disabled = true;
                    reasonSelect.required = false;
                } else {
                    reasonWrap.classList.remove('hidden');
                    reasonSelect.disabled = false;
                    reasonSelect.required = true;
                }

                toggleReasonDetail();
            }

            checkbox.addEventListener('change', toggleReasonDropdown);
            reasonSelect.addEventListener('change', toggleReasonDetail);

            toggleReasonDropdown();
        });
    });
</script>
@endif