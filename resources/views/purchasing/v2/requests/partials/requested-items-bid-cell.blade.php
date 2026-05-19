@php
$offer = $offers->get($rank);
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

        <input type="text" name="vendor_offers[{{ $requestItem->id }}][{{ $rank }}][offer_total]" value="{{ $formatRupiahInput(old($prefix . '.offer_total', $offer?->offer_total)) }}" inputmode="numeric" class="vendor-price-input w-full border border-gray-300 px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Rp 0">
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