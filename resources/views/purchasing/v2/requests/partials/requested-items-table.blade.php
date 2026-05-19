@php
$userRole = strtolower((string) (auth()->user()->role ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$isRequesterAccount = in_array($normalizedRole, [
'requester',
], true);

$isPurchasingAccount = in_array($normalizedRole, [
'purchasing',
'purchase',
'purchasing staff',
], true);

$isCostControlAccount = in_array($normalizedRole, [
'accounting',
'cost control',
'cost controller',
], true);

$hasVendorOffers = $purchaseRequest->items->contains(function ($item) {
return $item->vendorOffers->isNotEmpty();
});

$showEstimatedPriceColumns = true;
$showEstimatedTotalPriceColumn = false;

/*
|--------------------------------------------------------------------------
| Selected Vendor Columns Visibility
|--------------------------------------------------------------------------
| Hide Selected Vendor / Price per Unit / Total Price from requester,
| purchasing, and cost control. Cost control chooses directly from Bid columns.
*/
$showSelectedVendorColumns = ! $isRequesterAccount
&& ! $isPurchasingAccount
&& ! $isCostControlAccount
&& $hasVendorOffers;

$isGmUser = in_array($normalizedRole, [
'admin',
'gm',
'general manager',
], true);

/*
|--------------------------------------------------------------------------
| Bid Columns Visibility
|--------------------------------------------------------------------------
| Hide Bid 1, Bid 2, Bid 3 for requester and GM.
*/
$showBidColumns = ! $isRequesterAccount
&& ! in_array($normalizedRole, [
'gm',
'general manager',
], true);

$canGmApproveItems = $isGmUser && $purchaseRequest->status === 'submitted_to_gm';

$purchasingEditableStatuses = [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
'on_hold_by_gm',
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
&& $purchaseRequest->status === 'submitted_to_accounting'
&& $hasVendorOffers;

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

$tableMinWidthClass = match (true) {
$showBidColumns && $showSelectedVendorColumns => 'min-w-[2400px]',
$showBidColumns && ! $showSelectedVendorColumns => 'min-w-[1750px]',
$showSelectedVendorColumns => 'min-w-[1600px]',
default => 'min-w-[1200px]',
};

$tableColumnCount =
6
+ ($canGmApproveItems ? 1 : 0)
+ ($showEstimatedPriceColumns ? 1 : 0)
+ ($showEstimatedTotalPriceColumn ? 1 : 0)
+ ($showSelectedVendorColumns ? 3 : 0)
+ ($showBidColumns ? 3 : 0);

$formatRupiahInput = function ($value): string {
if ($value === null || $value === '') {
return '';
}

if (is_numeric($value)) {
$number = (float) $value;

if ($number <= 0) { return '' ; } return 'Rp ' . number_format($number, 0, ',' , '.' ); } $raw=trim((string) $value); $clean=preg_replace('/[^0-9,\.]/', '' , $raw); if ($clean==='' ) { return '' ; } if (str_contains($clean, ',' ) && str_contains($clean, '.' )) { $lastComma=strrpos($clean, ',' ); $lastDot=strrpos($clean, '.' ); if ($lastComma> $lastDot) {
    $clean = str_replace('.', '', $clean);
    $clean = str_replace(',', '.', $clean);
    } else {
    $clean = str_replace(',', '', $clean);
    }
    } elseif (str_contains($clean, ',')) {
    $clean = str_replace('.', '', $clean);
    $clean = str_replace(',', '.', $clean);
    } elseif (str_contains($clean, '.')) {
    $parts = explode('.', $clean);

    if (count($parts) > 2) {
    $clean = str_replace('.', '', $clean);
    } else {
    $decimalPart = $parts[1] ?? '';

    if (strlen($decimalPart) === 3) {
    $clean = str_replace('.', '', $clean);
    }
    }
    }

    $number = (float) $clean;

    if ($number <= 0) { return '' ; } return 'Rp ' . number_format($number, 0, ',' , '.' ); }; @endphp <div class="mb-2">
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
                        <table class="w-full {{ $tableMinWidthClass }} border-collapse text-sm">
                            @include('purchasing.v2.requests.partials.requested-items-table-head')

                            <tbody>
                                @forelse ($purchaseRequest->items as $requestItem)
                                @include('purchasing.v2.requests.partials.requested-items-row', [
                                'requestItem' => $requestItem,
                                'rowNumber' => $loop->iteration,
                                ])
                                @empty
                                <tr>
                                    <td colspan="{{ $tableColumnCount }}" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                                        No items found.
                                    </td>
                                </tr>
                                @endforelse

                                @include('purchasing.v2.requests.partials.requested-items-grand-total-row')
                            </tbody>
                        </table>
                    </div>

                    @include('purchasing.v2.requests.partials.requested-items-actions')

                    @include('purchasing.v2.requests.partials.requested-items-scripts')