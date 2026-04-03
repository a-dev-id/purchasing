@php
use Illuminate\Support\Facades\Storage;

$offers = $purchaseRequest->vendorOffers
->sortBy([
['offer_rank', 'asc'],
['id', 'asc'],
])
->take(3)
->values();

$bid1 = $offers->firstWhere('offer_rank', 1) ?? $offers->get(0);
$bid2 = $offers->firstWhere('offer_rank', 2) ?? $offers->get(1);
$bid3 = $offers->firstWhere('offer_rank', 3) ?? $offers->get(2);

$selectedOffer = $purchaseRequest->vendorOffers->firstWhere('is_selected_by_accounting', true);

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

$rowsToShow = max($purchaseRequest->items->count(), 1);

$itemsTotal = $purchaseRequest->items->sum(function ($item) {
$qty = (float) ($item->qty ?? $item->quantity ?? 0);
$unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);

return $qty * $unitPrice;
});

$displayTotalAmount = $selectedOffer?->offer_total;

if (($displayTotalAmount === null || $displayTotalAmount === '') && $itemsTotal > 0) {
$displayTotalAmount = $itemsTotal;
}

$itemsWithPhotos = $purchaseRequest->items
->map(function ($item) {
$photos = collect($item->photos ?? [])
->filter(fn ($photo) => filled($photo->file_path))
->map(function ($photo) {
return [
'name' => $photo->file_name ?: 'Photo',
'url' => Storage::disk('public')->url($photo->file_path),
];
})
->values();

return [
'item_name' => $item->item?->name ?? $item->item_name ?? 'Item',
'photos' => $photos,
];
})
->filter(fn ($item) => $item['photos']->isNotEmpty())
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

        .req-no {
            font-size: 18px;
            font-weight: 700;
            text-align: right;
            margin-top: 2px;
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
            vertical-align: top;
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
            min-height: 104px;
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

        .sign-name {
            margin-top: 26px;
            font-weight: 400;
            text-transform: none;
        }

        .sign-date {
            padding: 8px 10px;
            border-top: 1px solid #111827;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
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
        <a href="{{ url()->previous() }}" class="btn">Back</a>
        <a href="javascript:window.print()" class="btn">Print</a>
    </div>

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
                @for ($i = 0; $i < $rowsToShow; $i++) @php $item=$purchaseRequest->items->get($i);

                    $qty = $item->qty ?? $item->quantity ?? null;
                    $unitPrice = $item->unit_price ?? $item->price ?? null;
                    $lineTotal = ($qty !== null && $unitPrice !== null) ? ((float) $qty * (float) $unitPrice) : null;

                    $vendorMode = $purchaseRequest->vendor_comparison_mode ?? 'item';
                    $isPrVendorMode = $vendorMode === 'pr';
                    $isFirstRow = $i === 0;

                    if ($item && ! $isPrVendorMode) {
                    $itemOffers = $item->vendorOffers
                    ->sortBy([
                    ['offer_rank', 'asc'],
                    ['id', 'asc'],
                    ])
                    ->values();

                    $rowBid1 = $itemOffers->firstWhere('offer_rank', 1);
                    $rowBid2 = $itemOffers->firstWhere('offer_rank', 2);
                    $rowBid3 = $itemOffers->firstWhere('offer_rank', 3);

                    $rowBid1 = $rowBid1 ?? $itemOffers->get(0);
                    $rowBid2 = $rowBid2 ?? $itemOffers->get(1);
                    $rowBid3 = $rowBid3 ?? $itemOffers->get(2);
                    } else {
                    $rowBid1 = $bid1;
                    $rowBid2 = $bid2;
                    $rowBid3 = $bid3;
                    }
                    @endphp

                    <tr>
                        <td class="text-right">{{ $item ? $formatMoney($unitPrice) : '-' }}</td>
                        <td class="text-center">{{ $qty ?? '-' }}</td>
                        <td>
                            @if ($item)
                            <div class="item-name">
                                {{ $item->item?->name ?? $item->item_name ?? 'Item' }}
                            </div>

                            @if (!empty($item->specification))
                            <div class="item-notes">{{ $cleanRichText($item->specification) }}</div>
                            @endif

                            @if (!empty($item->purpose))
                            <div class="item-notes" style="margin-top: 8px;">{{ $cleanRichText($item->purpose) }}</div>
                            @endif
                            @else
                            &nbsp;
                            @endif
                        </td>

                        @if ($isPrVendorMode)
                        @if ($isFirstRow)
                        <td class="text-center" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid1)
                            <div style="font-weight: 700;">
                                {{ $rowBid1->vendor?->name ?? $rowBid1->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid1->offer_total, $rowBid1->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid2)
                            <div style="font-weight: 700;">
                                {{ $rowBid2->vendor?->name ?? $rowBid2->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid2->offer_total, $rowBid2->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center" rowspan="{{ $rowsToShow }}">
                            @if ($rowBid3)
                            <div style="font-weight: 700;">
                                {{ $rowBid3->vendor?->name ?? $rowBid3->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid3->offer_total, $rowBid3->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>
                        @endif
                        @else
                        <td class="text-center">
                            @if ($rowBid1)
                            <div style="font-weight: 700;">
                                {{ $rowBid1->vendor?->name ?? $rowBid1->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid1->offer_total, $rowBid1->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center">
                            @if ($rowBid2)
                            <div style="font-weight: 700;">
                                {{ $rowBid2->vendor?->name ?? $rowBid2->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid2->offer_total, $rowBid2->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>

                        <td class="text-center">
                            @if ($rowBid3)
                            <div style="font-weight: 700;">
                                {{ $rowBid3->vendor?->name ?? $rowBid3->vendor_name ?? '-' }}
                            </div>
                            <div style="margin-top: 4px; font-size: 11px;">
                                {{ $formatMoney($rowBid3->offer_total, $rowBid3->currency ?? 'IDR') }}
                            </div>
                            @else
                            -
                            @endif
                        </td>
                        @endif

                        <td class="text-right">{{ $item ? $formatMoney($lineTotal) : '-' }}</td>
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
                        <td class="left-label">Total Amount</td>
                        <td>{{ $formatMoney($displayTotalAmount, $selectedOffer?->currency ?? 'IDR') }}</td>
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

        <div class="signatures">
            <div class="sign-box">
                <div class="sign-title">
                    Purchasing Mgr
                    <div class="sign-name">-</div>
                </div>
                <div class="sign-date">Date</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Cost Controller
                    <div class="sign-name">-</div>
                </div>
                <div class="sign-date">Date</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    GM
                    <div class="sign-name">-</div>
                </div>
                <div class="sign-date">Date</div>
            </div>

            <div class="sign-box">
                <div class="sign-title">
                    Financial Controller
                    <div class="sign-name">-</div>
                </div>
                <div class="sign-date">Date</div>
            </div>
        </div>
    </div>
</body>

</html>