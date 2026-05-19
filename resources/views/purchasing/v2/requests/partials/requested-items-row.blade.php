@php
$qty = (float) ($requestItem->qty ?? 0);
$photos = $requestItem->item?->photos ?? collect();
$offers = $requestItem->vendorOffers->keyBy('offer_rank');

$estimatedUnitPrice = (float) (
$requestItem->estimated_unit_price
?? $requestItem->est_unit_price
?? $requestItem->unit_price
?? $requestItem->item?->last_price
?? 0
);

$estimatedTotalPrice = $qty * $estimatedUnitPrice;

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
        {{ $rowNumber }}
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

    @if ($showEstimatedPriceColumns)
    <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right">
        @if ($estimatedUnitPrice > 0)
        Rp {{ number_format($estimatedUnitPrice, 0, ',', '.') }}
        @else
        -
        @endif
    </td>
    @endif

    @if ($showEstimatedTotalPriceColumn)
    <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right font-semibold">
        @if ($estimatedTotalPrice > 0)
        Rp {{ number_format($estimatedTotalPrice, 0, ',', '.') }}
        @else
        -
        @endif
    </td>
    @endif

    @if ($showSelectedVendorColumns)
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
    @endif

    @if ($showBidColumns)
    @for ($rank = 1; $rank <= 3; $rank++) @include('purchasing.v2.requests.partials.requested-items-bid-cell', [ 'requestItem'=> $requestItem,
        'offers' => $offers,
        'rank' => $rank,
        ])
        @endfor
        @endif

        <td class="border border-gray-300 px-3 py-2 min-w-[250px]">
            {{ strip_tags($requestItem->specification ?? '-') }}
        </td>
</tr>