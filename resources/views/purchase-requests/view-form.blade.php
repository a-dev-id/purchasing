@php
use App\Models\PurchaseRequestLog;
use Illuminate\Support\Facades\Storage;

$items = collect($purchaseRequest->items ?? []);

$prOffers = collect($purchaseRequest->vendorOffers ?? [])
->sortBy([
['offer_rank', 'asc'],
['id', 'asc'],
])
->values();

$bid1 = $prOffers->firstWhere('offer_rank', 1) ?? $prOffers->get(0);
$bid2 = $prOffers->firstWhere('offer_rank', 2) ?? $prOffers->get(1);
$bid3 = $prOffers->firstWhere('offer_rank', 3) ?? $prOffers->get(2);

$vendorMode = $purchaseRequest->vendor_comparison_mode ?? 'item';
$isPrVendorMode = $vendorMode === 'pr';

$getItemQty = function ($item): float {
return (float) ($item->qty ?? $item->quantity ?? 0);
};

$getOfferCurrency = function ($offer): string {
if (! $offer) {
return 'IDR';
}

return $offer->currency
?? $offer->currency_code
?? 'IDR';
};

$getOfferUnitPrice = function ($offer): ?float {
if (! $offer) {
return null;
}

foreach ([
'offer_price',
'offer_unit_price',
'price',
'unit_price',
'unit_cost',
'cost_per_unit',
] as $field) {
$value = data_get($offer, $field);

if ($value !== null && $value !== '') {
return (float) $value;
}
}

return null;
};

$getOfferLineTotal = function ($offer, $qty = 1) use ($getOfferUnitPrice): ?float {
if (! $offer) {
return null;
}

foreach ([
'offer_total',
'total_price',
'total_amount',
'grand_total',
'line_total',
'amount',
'total',
] as $field) {
$value = data_get($offer, $field);

if ($value !== null && $value !== '') {
return (float) $value;
}
}

$unitPrice = $getOfferUnitPrice($offer);

if ($unitPrice !== null) {
return (float) $qty * $unitPrice;
}

return null;
};

$getOfferIdentity = function ($offer): ?string {
if (! $offer) {
return null;
}

$id = data_get($offer, 'id');

if (filled($id)) {
return 'id:' . $id;
}

$vendorId = data_get($offer, 'vendor_id') ?? data_get($offer, 'vendor.id');
$vendorName = strtolower(trim((string) (data_get($offer, 'vendor_name') ?? data_get($offer, 'vendor.name') ?? '')));
$amount = data_get($offer, 'offer_total') ?? data_get($offer, 'offer_price') ?? data_get($offer, 'price') ?? '';
$currency = strtolower(trim((string) ($offer->currency ?? data_get($offer, 'currency_code') ?? 'idr')));

return 'fallback:' . ($vendorId ?: '-') . '|' . $vendorName . '|' . $amount . '|' . $currency;
};

$isSameOffer = function ($left, $right) use ($getOfferIdentity): bool {
if (! $left || ! $right) {
return false;
}

return $getOfferIdentity($left) === $getOfferIdentity($right);
};

$resolveSelectedOffer = function ($owner, $offers) {
$offers = collect($offers ?? [])->values();

if ($offers->isEmpty()) {
return null;
}

foreach ([
'is_selected_by_accounting',
'is_selected',
'is_approved',
'is_chosen',
'is_final',
'selected',
] as $flagField) {
$found = $offers->first(function ($offer) use ($flagField) {
return (bool) data_get($offer, $flagField);
});

if ($found) {
return $found;
}
}

foreach ([
'selected_vendor_offer_id',
'accounting_selected_vendor_offer_id',
'approved_vendor_offer_id',
'chosen_vendor_offer_id',
'final_vendor_offer_id',
'vendor_offer_id',
] as $idField) {
$selectedOfferId = data_get($owner, $idField);

if ($selectedOfferId !== null && $selectedOfferId !== '') {
$found = $offers->first(function ($offer) use ($selectedOfferId) {
return (string) data_get($offer, 'id') === (string) $selectedOfferId;
});

if ($found) {
return $found;
}
}
}

foreach ([
'selected_vendor_id',
'accounting_selected_vendor_id',
'approved_vendor_id',
'chosen_vendor_id',
'final_vendor_id',
'vendor_id',
] as $vendorIdField) {
$selectedVendorId = data_get($owner, $vendorIdField);

if ($selectedVendorId !== null && $selectedVendorId !== '') {
$found = $offers->first(function ($offer) use ($selectedVendorId) {
$offerVendorId = data_get($offer, 'vendor_id') ?? data_get($offer, 'vendor.id');

return (string) $offerVendorId === (string) $selectedVendorId;
});

if ($found) {
return $found;
}
}
}

if ($offers->count() === 1) {
return $offers->first();
}

return null;
};

$selectedPrOffer = $resolveSelectedOffer($purchaseRequest, $prOffers);
$selectedPrOfferTotal = $selectedPrOffer ? $getOfferLineTotal($selectedPrOffer, 1) : null;
$selectedPrOfferCurrency = $selectedPrOffer ? $getOfferCurrency($selectedPrOffer) : 'IDR';

$selectedItemOffers = $items
->map(function ($item) use ($resolveSelectedOffer) {
$itemOffers = collect($item->vendorOffers ?? [])
->sortBy([
['offer_rank', 'asc'],
['id', 'asc'],
])
->values();

return [
'item' => $item,
'offer' => $resolveSelectedOffer($item, $itemOffers),
];
})
->filter(fn ($row) => filled($row['offer']))
->values();

$formatMoney = function ($amount, $currency = 'IDR') {
if ($amount === null || $amount === '') {
return '-';
}

return $currency . ' ' . number_format((float) $amount, 0, ',', '.');
};

$cleanRichText = function (?string $html): string {
if (blank($html)) {
return '-';
}

$text = str($html)
->replace(['<br>', '<br />', '<br />'], "\n")
->replace(['</p>'], "\n\n")
->replace(['<li>'], '• ')
    ->replace(['</li>'], "\n")
->toString();

return trim(html_entity_decode(strip_tags($text)));
};

$dateNeeded = filled($purchaseRequest->date_needed)
? \Carbon\Carbon::parse($purchaseRequest->date_needed)->format('d M Y')
: '-';

$submittedDate = filled($purchaseRequest->submitted_at)
? $purchaseRequest->submitted_at->timezone('Asia/Makassar')->format('d M Y H:i')
: '-';

$normalize = function ($value) {
if ($value === null || $value === '') {
return null;
}

return strtolower(trim((string) $value));
};

$currentStatus = $normalize($purchaseRequest->status ?? null);

$workflowLogs = PurchaseRequestLog::query()
->where('purchase_request_id', $purchaseRequest->id)
->orderBy('acted_at')
->orderBy('created_at')
->get();

$findLatestLog = function (array $actions = [], array $toStatuses = [], array $roles = []) use ($workflowLogs, $normalize) {
$actions = collect($actions)->map(fn ($value) => $normalize($value))->filter()->values()->all();
$toStatuses = collect($toStatuses)->map(fn ($value) => $normalize($value))->filter()->values()->all();
$roles = collect($roles)->map(fn ($value) => $normalize($value))->filter()->values()->all();

return $workflowLogs->last(function ($log) use ($actions, $toStatuses, $roles, $normalize) {
$action = $normalize($log->action ?? null);
$toStatus = $normalize($log->to_status ?? null);
$role = $normalize($log->role_name ?? null);

return in_array($action, $actions, true)
|| in_array($toStatus, $toStatuses, true)
|| in_array($role, $roles, true);
});
};

$getLogTimestamp = function ($log) {
if (! $log) {
return null;
}

$date = $log->acted_at ?? $log->created_at;

return filled($date) ? \Carbon\Carbon::parse($date) : null;
};

$isLogAfter = function ($candidate, $reference) use ($getLogTimestamp): bool {
if (! $candidate) {
return false;
}

if (! $reference) {
return true;
}

$candidateAt = $getLogTimestamp($candidate);
$referenceAt = $getLogTimestamp($reference);

if (! $candidateAt) {
return false;
}

if (! $referenceAt) {
return true;
}

return $candidateAt->gt($referenceAt);
};

$latestSubmittedToAccounting = $findLatestLog(
actions: ['submitted_to_accounting', 'submit_to_accounting', 'sent_to_accounting'],
toStatuses: ['submitted_to_accounting']
);

$latestSubmittedToGm = $findLatestLog(
actions: ['submitted_to_gm', 'submit_to_gm', 'sent_to_gm'],
toStatuses: ['submitted_to_gm']
);

$latestReturnedToPurchasing = $findLatestLog(
actions: ['revision_to_purchasing_from_accounting', 'revision_to_purchasing_from_gm'],
toStatuses: ['revision_to_purchasing_from_accounting', 'revision_to_purchasing_from_gm']
);

$latestReturnedFromGm = $findLatestLog(
actions: [
'revision_to_accounting_from_gm',
'revision_to_purchasing_from_gm',
'revision_to_requester_from_gm',
],
toStatuses: [
'revision_to_accounting_from_gm',
'revision_to_purchasing_from_gm',
'revision_to_requester_from_gm',
]
);

$purchasingApproval = $latestSubmittedToAccounting;

if (! $isLogAfter($purchasingApproval, $latestReturnedToPurchasing)) {
$purchasingApproval = null;
}

$accountingApproval = $latestSubmittedToGm;

if (! $isLogAfter($accountingApproval, $latestSubmittedToAccounting)) {
$accountingApproval = null;
}

if (! $isLogAfter($accountingApproval, $latestReturnedFromGm)) {
$accountingApproval = null;
}

if (! $accountingApproval && in_array($currentStatus, ['submitted_to_gm', 'gm_approved', 'approved'], true)) {
$fallbackAccountingApproval = $findLatestLog(
roles: ['accounting', 'cost controller']
);

if (
$isLogAfter($fallbackAccountingApproval, $latestSubmittedToAccounting)
&& $isLogAfter($fallbackAccountingApproval, $latestReturnedFromGm)
) {
$accountingApproval = $fallbackAccountingApproval;
}
}

$gmApproval = $findLatestLog(
actions: ['gm_approved', 'approved', 'approve'],
toStatuses: ['gm_approved', 'approved'],
roles: ['gm', 'general manager']
);

if (! $isLogAfter($gmApproval, $latestSubmittedToGm)) {
$gmApproval = null;
}

if (! $isLogAfter($gmApproval, $latestReturnedFromGm)) {
$gmApproval = null;
}

$latestFinancialControllerLog = $findLatestLog(
actions: [
'pending',
'on_progress',
'waiting_payment',
'paid_to_vendor',
'on_shipping',
'received_by_requester',
'waiting_payment_by_fc',
'paid_to_vendor_by_fc',
'item_arrived_by_fc',
'received_by_requester_by_fc',
'on_hold_by_fc',
'approved',
'final_approved',
'approved_by_financial_controller',
],
toStatuses: [
'pending',
'on_progress',
'waiting_payment',
'paid_to_vendor',
'on_shipping',
'received_by_requester',
'waiting_payment_by_fc',
'paid_to_vendor_by_fc',
'item_arrived_by_fc',
'received_by_requester_by_fc',
'on_hold_by_fc',
'approved',
'final_approved',
]
);

$financialControllerApproval = $findLatestLog(
actions: [
'received_by_requester',
'received_by_requester_by_fc',
'approved',
'final_approved',
'approved_by_financial_controller',
],
toStatuses: [
'received_by_requester',
'received_by_requester_by_fc',
'approved',
'final_approved',
]
);

$approvalDate = function ($log) {
if (! $log) {
return '-';
}

$date = $log->acted_at ?? $log->created_at;

return filled($date)
? \Carbon\Carbon::parse($date)->timezone('Asia/Makassar')->format('d M Y H:i')
: '-';
};

$financialControllerStatusLabel = match ($currentStatus) {
'gm_approved' => 'Waiting FC Action',
'pending' => 'Pending',
'on_progress' => 'On Progress',
'waiting_payment' => 'Waiting Payment',
'paid_to_vendor' => 'Paid to Vendor',
'on_shipping' => 'On Shipping',
'received_by_requester' => 'Received by Requester (Done)',
'cancelled' => 'Cancelled',

'waiting_payment_by_fc' => 'Waiting Payment',
'paid_to_vendor_by_fc' => 'Paid to Vendor',
'item_arrived_by_fc' => 'On Shipping',
'received_by_requester_by_fc' => 'Received by Requester (Done)',
'on_hold_by_fc' => 'On Hold',
'approved' => 'Completed',

default => $financialControllerApproval ? 'Completed' : '-',
};

$financialControllerStatusClass = match ($currentStatus) {
'gm_approved', 'pending', 'waiting_payment', 'waiting_payment_by_fc' => 'status-warning',
'on_progress', 'on_shipping', 'item_arrived_by_fc' => 'status-info',
'paid_to_vendor', 'paid_to_vendor_by_fc', 'received_by_requester', 'received_by_requester_by_fc', 'approved' => 'status-success',
'cancelled' => 'status-danger',
'on_hold_by_fc' => 'status-muted',
default => $financialControllerApproval ? 'status-success' : '',
};

$financialControllerStatusDate = '-';

if (in_array($currentStatus, [
'pending',
'on_progress',
'waiting_payment',
'paid_to_vendor',
'on_shipping',
'received_by_requester',
'waiting_payment_by_fc',
'paid_to_vendor_by_fc',
'item_arrived_by_fc',
'received_by_requester_by_fc',
'on_hold_by_fc',
'approved',
'final_approved',
], true) && $latestFinancialControllerLog) {
$financialControllerStatusDate = $approvalDate($latestFinancialControllerLog);
} elseif ($currentStatus === 'gm_approved') {
$financialControllerStatusDate = '-';
}

$rowsToShow = max($items->count(), 1);

$itemsTotal = $items->sum(function ($item) {
$qty = (float) ($item->qty ?? $item->quantity ?? 0);
$unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);

return $qty * $unitPrice;
});

$selectedItemsTotal = $selectedItemOffers->sum(function ($row) use ($getItemQty, $getOfferLineTotal) {
return (float) ($getOfferLineTotal($row['offer'], $getItemQty($row['item'])) ?? 0);
});

$displayTotalAmount = null;
$displayCurrency = 'IDR';

if ($isPrVendorMode) {
if ($selectedPrOffer) {
$displayTotalAmount = $getOfferLineTotal($selectedPrOffer, 1);
$displayCurrency = $getOfferCurrency($selectedPrOffer);
}
} else {
if ($selectedItemOffers->isNotEmpty()) {
$displayTotalAmount = $selectedItemsTotal;

$currencies = $selectedItemOffers
->map(fn ($row) => $getOfferCurrency($row['offer']))
->filter()
->unique()
->values();

$displayCurrency = $currencies->count() === 1
? $currencies->first()
: 'IDR';
}
}

if (($displayTotalAmount === null || $displayTotalAmount === '') && $itemsTotal > 0) {
$displayTotalAmount = $itemsTotal;
}

$itemsWithPhotos = $items
->map(function ($purchaseRequestItem) {
$photos = collect($purchaseRequestItem->photos ?? []);

if ($photos->isEmpty() && $purchaseRequestItem->item) {
$photos = collect($purchaseRequestItem->item->photos ?? []);
}

$photos = $photos
->filter(function ($photo) {
return filled($photo->file_path ?? null);
})
->map(function ($photo) {
$filePath = trim((string) ($photo->file_path ?? ''));

return [
'name' => $photo->file_name ?: (basename($filePath) ?: 'Photo'),
'url' => Storage::disk('public')->url($filePath),
];
})
->values();

return [
'item_name' => $purchaseRequestItem->item?->name ?? $purchaseRequestItem->item_name ?? 'Item',
'photos' => $photos,
];
})
->filter(function ($row) {
return $row['photos']->isNotEmpty();
})
->values();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request {{ $purchaseRequest->request_number ?? $purchaseRequest->id }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
        }

        .print-actions {
            max-width: 1400px;
            margin: 0 auto 14px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: 1px solid #111827;
            background: #fff;
            color: #111827;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.2;
        }

        .btn:hover {
            background: #111827;
            color: #fff;
        }

        .page {
            max-width: 1400px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 24px;
        }

        .top-grid {
            display: grid;
            grid-template-columns: 1.08fr 1.12fr 1fr;
            border: 1px solid #111827;
            border-bottom: 0;
        }

        .box {
            border-right: 1px solid #111827;
            min-height: 150px;
        }

        .box:last-child {
            border-right: 0;
        }

        .box table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .box td {
            border-bottom: 1px solid #111827;
            padding: 8px 10px;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.2;
        }

        .box tr:last-child td {
            border-bottom: 0;
        }

        .label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 2px;
        }

        .center-box {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .title {
            font-size: 25px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #111827;
            padding: 5px 8px;
            font-size: 12px;
            vertical-align: top;
        }

        .main-table th {
            text-transform: uppercase;
            text-align: center;
            font-weight: 700;
            line-height: 1.1;
        }

        .main-table tbody tr {
            height: 27px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .item-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .item-notes,
        .remarks-content {
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-line;
            word-break: break-word;
        }

        .selected-vendor-cell {
            background: #ecfdf5 !important;
        }

        .selected-vendor-name {
            color: #166534;
        }

        .selected-vendor-badge {
            display: inline-block;
            margin-bottom: 6px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
            border-radius: 999px;
        }

        .remarks-grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            border-left: 1px solid #111827;
            border-right: 1px solid #111827;
            border-bottom: 1px solid #111827;
        }

        .remarks-left {
            min-height: 180px;
            border-right: 1px solid #111827;
        }

        .remarks-left .row-title,
        .remarks-right .row-title,
        .photos-section .row-title {
            border-bottom: 1px solid #111827;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .remarks-left .row-body {
            min-height: 140px;
            padding: 10px;
        }

        .remarks-right table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .remarks-right td {
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #111827;
        }

        .remarks-right tr:last-child td {
            border-bottom: 0;
        }

        .remarks-right .left-label {
            width: 48%;
            font-weight: 700;
            text-transform: uppercase;
        }

        .total-amount-label {
            vertical-align: middle !important;
        }

        .total-amount-cell {
            vertical-align: middle !important;
            text-align: center;
        }

        .total-amount-value {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 150px;
            width: 100%;
            font-size: 28px !important;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
        }

        .photos-section {
            border-left: 1px solid #111827;
            border-right: 1px solid #111827;
            border-bottom: 1px solid #111827;
        }

        .photos-body {
            padding: 12px;
        }

        .photo-item+.photo-item {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px dashed #9ca3af;
        }

        .photo-item-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .photo-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .photo-card {
            width: 180px;
        }

        .photo-card a {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .photo-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border: 1px solid #d1d5db;
            display: block;
            background: #fff;
        }

        .photo-caption {
            margin-top: 6px;
            font-size: 11px;
            line-height: 1.35;
            word-break: break-word;
        }

        .empty-photos {
            font-size: 12px;
            color: #6b7280;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-left: 1px solid #111827;
            border-right: 1px solid #111827;
            border-bottom: 1px solid #111827;
        }

        .sign-box {
            min-height: 132px;
            border-right: 1px solid #111827;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sign-box:last-child {
            border-right: 0;
        }

        .sign-title {
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sign-status {
            margin-top: 18px;
            font-size: 12px;
            font-weight: 700;
            color: #15803d;
            text-transform: none;
            line-height: 1.35;
        }

        .sign-status.status-warning {
            color: #b45309;
        }

        .sign-status.status-muted {
            color: #6b7280;
        }

        .sign-status.status-success {
            color: #15803d;
        }

        .sign-status.status-info {
            color: #1d4ed8;
        }

        .sign-status.status-danger {
            color: #b91c1c;
        }

        .sign-date {
            padding: 8px 10px;
            border-top: 1px solid #111827;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sign-date-value {
            margin-top: 4px;
            font-weight: 400;
            text-transform: none;
            line-height: 1.35;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .print-actions {
                display: none;
            }

            .page {
                border: 0;
                max-width: 100%;
                padding: 0;
            }

            .photos-section {
                display: none;
            }
        }

    </style>
</head>

<body>
    <div class="print-actions">
        <button type="button" class="btn" onclick="closePreviewTab()">Back</button>
        <a href="javascript:window.print()" class="btn">Print</a>
    </div>

    <script>
        function closePreviewTab() {
            window.close();

            setTimeout(function () {
                if (!window.closed) {
                    window.history.back();
                }
            }, 100);
        }
    </script>

    <div class="page">
        <div class="top-grid">
            <div class="box">
                <table>
                    <tr>
                        <td>
                            <div class="label">Department</div>
                            <div>{{ $purchaseRequest->department_name ?: '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Purpose</div>
                            <div>{{ $purchaseRequest->title ?: '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Date Needed</div>
                            <div>{{ $dateNeeded }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="box center-box">
                <div class="title">PURCHASE REQUEST</div>
            </div>

            <div class="box">
                <table>
                    <tr>
                        <td>
                            <div class="label">Purchase Requisition Number</div>
                            <div style="font-size: 18px; font-weight: 700; text-align: right;">
                                {{ $purchaseRequest->request_number ?? str_pad((string) $purchaseRequest->id, 4, '0', STR_PAD_LEFT) }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Submitted Date</div>
                            <div>{{ $submittedDate }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="main-table">
            <colgroup>
                <col style="width: 12%;">
                <col style="width: 10%;">
                <col style="width: 34%;">
                <col style="width: 11%;">
                <col style="width: 11%;">
                <col style="width: 11%;">
                <col style="width: 11%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Cost Per Unit</th>
                    <th>To Order</th>
                    <th>Article &amp; Description</th>
                    <th>Bid 1</th>
                    <th>Bid 2</th>
                    <th>Bid 3</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $rowsToShow; $i++) @php $item=$items->get($i);

                    $qty = $item ? ($item->qty ?? $item->quantity ?? null) : null;
                    $baseUnitPrice = $item ? ($item->unit_price ?? $item->price ?? null) : null;
                    $baseLineTotal = ($qty !== null && $baseUnitPrice !== null)
                    ? ((float) $qty * (float) $baseUnitPrice)
                    : null;

                    $rowDisplayUnitPrice = $baseUnitPrice;
                    $rowDisplayCurrency = 'IDR';
                    $rowFinalTotal = $baseLineTotal;
                    $rowFinalCurrency = 'IDR';

                    $isFirstRow = $i === 0;

                    if ($item && ! $isPrVendorMode) {
                    $itemOffers = collect($item->vendorOffers ?? [])
                    ->sortBy([
                    ['offer_rank', 'asc'],
                    ['id', 'asc'],
                    ])
                    ->values();

                    $rowBid1 = $itemOffers->firstWhere('offer_rank', 1) ?? $itemOffers->get(0);
                    $rowBid2 = $itemOffers->firstWhere('offer_rank', 2) ?? $itemOffers->get(1);
                    $rowBid3 = $itemOffers->firstWhere('offer_rank', 3) ?? $itemOffers->get(2);

                    $selectedRowOffer = $resolveSelectedOffer($item, $itemOffers);

                    if ($selectedRowOffer) {
                    $selectedCurrency = $getOfferCurrency($selectedRowOffer);
                    $selectedUnitPrice = $getOfferUnitPrice($selectedRowOffer);
                    $selectedLineTotal = $getOfferLineTotal($selectedRowOffer, $getItemQty($item));

                    if ($selectedUnitPrice !== null) {
                    $rowDisplayUnitPrice = $selectedUnitPrice;
                    $rowDisplayCurrency = $selectedCurrency;
                    } elseif ($selectedLineTotal !== null && (float) $getItemQty($item) > 0) {
                    $rowDisplayUnitPrice = $selectedLineTotal / (float) $getItemQty($item);
                    $rowDisplayCurrency = $selectedCurrency;
                    }

                    if ($selectedLineTotal !== null) {
                    $rowFinalTotal = $selectedLineTotal;
                    $rowFinalCurrency = $selectedCurrency;
                    }
                    }
                    } else {
                    $rowBid1 = $bid1;
                    $rowBid2 = $bid2;
                    $rowBid3 = $bid3;

                    $selectedRowOffer = $selectedPrOffer;
                    }

                    $rowBid1Selected = $isSameOffer($rowBid1, $selectedRowOffer);
                    $rowBid2Selected = $isSameOffer($rowBid2, $selectedRowOffer);
                    $rowBid3Selected = $isSameOffer($rowBid3, $selectedRowOffer);
                    @endphp

                    <tr>
                        <td class="text-right">{{ $item ? $formatMoney($rowDisplayUnitPrice, $rowDisplayCurrency) : '-' }}</td>
                        <td class="text-center">{{ $qty ?? '-' }}</td>
                        <td>
                            @if ($item)
                            <div class="item-name">
                                {{ $item->item?->name ?? $item->item_name ?? 'Item' }}
                            </div>

                            @if (! empty($item->specification))
                            <div class="item-notes">{{ $cleanRichText($item->specification) }}</div>
                            @endif

                            @if (! empty($item->purpose))
                            <div class="item-notes" style="margin-top: 8px;">{{ $cleanRichText($item->purpose) }}</div>
                            @endif
                            @else
                            &nbsp;
                            @endif
                        </td>

                        @if ($isPrVendorMode)
                        @if ($isFirstRow)
                        <td class="text-center {{ $rowBid1Selected ? 'selected-vendor-cell' : '' }}" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid1)
                            @if ($rowBid1Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid1Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid1->vendor?->name ?? $rowBid1->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid1, 1), $getOfferCurrency($rowBid1)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center {{ $rowBid2Selected ? 'selected-vendor-cell' : '' }}" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid2)
                            @if ($rowBid2Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid2Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid2->vendor?->name ?? $rowBid2->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid2, 1), $getOfferCurrency($rowBid2)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center {{ $rowBid3Selected ? 'selected-vendor-cell' : '' }}" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid3)
                            @if ($rowBid3Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid3Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid3->vendor?->name ?? $rowBid3->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid3, 1), $getOfferCurrency($rowBid3)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-right {{ $selectedPrOffer ? 'selected-vendor-cell' : '' }}" rowspan="{{ $rowsToShow }}" style="vertical-align: middle; font-weight: 700;">
                            {{ $selectedPrOffer ? $formatMoney($selectedPrOfferTotal, $selectedPrOfferCurrency) : '-' }}
                        </td>
                        @endif
                        @else
                        <td class="text-center {{ $rowBid1Selected ? 'selected-vendor-cell' : '' }}">
                            @if ($rowBid1)
                            @if ($rowBid1Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid1Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid1->vendor?->name ?? $rowBid1->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid1, $qty ?: 1), $getOfferCurrency($rowBid1)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center {{ $rowBid2Selected ? 'selected-vendor-cell' : '' }}">
                            @if ($rowBid2)
                            @if ($rowBid2Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid2Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid2->vendor?->name ?? $rowBid2->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid2, $qty ?: 1), $getOfferCurrency($rowBid2)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center {{ $rowBid3Selected ? 'selected-vendor-cell' : '' }}">
                            @if ($rowBid3)
                            @if ($rowBid3Selected)
                            <div class="selected-vendor-badge">Selected Vendor</div>
                            @endif
                            <div class="item-name {{ $rowBid3Selected ? 'selected-vendor-name' : '' }}">
                                {{ $rowBid3->vendor?->name ?? $rowBid3->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($getOfferLineTotal($rowBid3, $qty ?: 1), $getOfferCurrency($rowBid3)) }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-right">
                            {{ $item ? $formatMoney($rowFinalTotal, $rowFinalCurrency) : '-' }}
                        </td>
                        @endif
                    </tr>
                    @endfor
            </tbody>
        </table>

        <div class="remarks-grid">
            <div class="remarks-left">
                <div class="row-title">Remarks</div>
                <div class="row-body remarks-content">
                    {{ $purchaseRequest->request_notes ? $cleanRichText($purchaseRequest->request_notes) : '-' }}
                </div>
            </div>

            <div class="remarks-right">
                <table>
                    <tr>
                        <td class="left-label total-amount-label">Total Amount</td>
                        <td class="total-amount-cell">
                            <div class="total-amount-value">
                                {{ $formatMoney($displayTotalAmount, $displayCurrency) }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="photos-section">
            <div class="row-title">Item Photos</div>

            <div class="photos-body">
                @if ($itemsWithPhotos->isEmpty())
                <div class="empty-photos">No item photos uploaded.</div>
                @else
                @foreach ($itemsWithPhotos as $photoItem)
                <div class="photo-item">
                    <div class="photo-item-title">{{ $photoItem['item_name'] }}</div>

                    <div class="photo-grid">
                        @foreach ($photoItem['photos'] as $photo)
                        <div class="photo-card">
                            <a href="{{ $photo['url'] }}" target="_blank">
                                <img src="{{ $photo['url'] }}" alt="{{ $photo['name'] }}">
                            </a>
                            <div class="photo-caption">{{ $photo['name'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <div class="photos-section">
            <div class="row-title">Vendor Offer Files</div>

            <div class="photos-body">
                @php
                $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                @endphp

                @if ($vendorMode === 'pr')
                @php
                $prVendorOffers = collect($purchaseRequest->vendorOffers ?? [])
                ->sortBy([
                ['offer_rank', 'asc'],
                ['id', 'asc'],
                ])
                ->filter(fn ($offer) => filled($offer->quotation_file))
                ->values();
                @endphp

                @if ($prVendorOffers->isEmpty())
                <div class="empty-photos">No vendor offer files uploaded.</div>
                @else
                <div class="photo-item">
                    <div class="photo-grid">
                        @foreach ($prVendorOffers as $offer)
                        @php
                        $filePath = $offer->quotation_file;
                        $fileUrl = \Illuminate\Support\Str::startsWith($filePath, ['http://', 'https://'])
                        ? $filePath
                        : \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);

                        $fileName = basename($filePath);
                        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $isImage = in_array($fileExt, $imageExtensions, true);
                        @endphp

                        <div class="photo-card">
                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 6px;">
                                {{ $offer->vendor?->name ?? $offer->vendor_name ?? 'Vendor' }}
                            </div>

                            @if ($isImage)
                            <a href="{{ $fileUrl }}" target="_blank">
                                <img src="{{ $fileUrl }}" alt="{{ $fileName }}">
                            </a>
                            <div class="photo-caption">{{ $fileName }}</div>
                            @else
                            <div class="photo-caption">
                                <a href="{{ $fileUrl }}" target="_blank" style="color: #111827; text-decoration: underline;">
                                    {{ $fileName }}
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @else
                @php
                $itemsWithVendorFiles = $items
                ->map(function ($item) {
                $offers = collect($item->vendorOffers ?? [])
                ->sortBy([
                ['offer_rank', 'asc'],
                ['id', 'asc'],
                ])
                ->filter(fn ($offer) => filled($offer->quotation_file))
                ->values();

                return [
                'item_name' => $item->item?->name ?? $item->item_name ?? 'Item',
                'offers' => $offers,
                ];
                })
                ->filter(fn ($item) => $item['offers']->isNotEmpty())
                ->values();
                @endphp

                @if ($itemsWithVendorFiles->isEmpty())
                <div class="empty-photos">No vendor offer files uploaded.</div>
                @else
                @foreach ($itemsWithVendorFiles as $vendorItem)
                <div class="photo-item">
                    <div class="photo-item-title">{{ $vendorItem['item_name'] }}</div>

                    <div class="photo-grid">
                        @foreach ($vendorItem['offers'] as $offer)
                        @php
                        $filePath = $offer->quotation_file;
                        $fileUrl = \Illuminate\Support\Str::startsWith($filePath, ['http://', 'https://'])
                        ? $filePath
                        : \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);

                        $fileName = basename($filePath);
                        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $isImage = in_array($fileExt, $imageExtensions, true);
                        @endphp

                        <div class="photo-card">
                            <div style="font-size: 12px; font-weight: 700; margin-bottom: 6px;">
                                {{ $offer->vendor?->name ?? $offer->vendor_name ?? 'Vendor' }}
                            </div>

                            @if ($isImage)
                            <a href="{{ $fileUrl }}" target="_blank">
                                <img src="{{ $fileUrl }}" alt="{{ $fileName }}">
                            </a>
                            <div class="photo-caption">{{ $fileName }}</div>
                            @else
                            <div class="photo-caption">
                                <a href="{{ $fileUrl }}" target="_blank" style="color: #111827; text-decoration: underline;">
                                    {{ $fileName }}
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
                @endif
                @endif
            </div>
        </div>

        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">
                    Purchasing Mgr
                    <div class="sign-status">
                        {{ $purchasingApproval ? 'Approved' : '-' }}
                    </div>
                </div>
                <div class="sign-date">
                    Date
                    <div class="sign-date-value">{{ $approvalDate($purchasingApproval) }}</div>
                </div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Cost Controller
                    <div class="sign-status">
                        {{ $accountingApproval ? 'Approved' : '-' }}
                    </div>
                </div>
                <div class="sign-date">
                    Date
                    <div class="sign-date-value">{{ $approvalDate($accountingApproval) }}</div>
                </div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    GM
                    <div class="sign-status">
                        {{ $gmApproval ? 'Approved' : '-' }}
                    </div>
                </div>
                <div class="sign-date">
                    Date
                    <div class="sign-date-value">{{ $approvalDate($gmApproval) }}</div>
                </div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Financial Controller
                    <div class="sign-status {{ $financialControllerStatusClass }}">
                        {{ $financialControllerStatusLabel }}
                    </div>
                </div>
                <div class="sign-date">
                    Date
                    <div class="sign-date-value">{{ $financialControllerStatusDate }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>