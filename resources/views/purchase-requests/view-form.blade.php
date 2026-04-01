@php
$offers = $purchaseRequest->vendorOffers
->sortBy([
['offer_rank', 'asc'],
['id', 'asc'],
])
->take(3)
->values();

$bid1 = $offers->get(0);
$bid2 = $offers->get(1);
$bid3 = $offers->get(2);

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

$submittedDate = $purchaseRequest->submitted_at
? $purchaseRequest->submitted_at->timezone('Asia/Makassar')->format('d M Y H:i')
: '-';

$rowsToShow = max($purchaseRequest->items->count(), 8);

$itemsTotal = $purchaseRequest->items->sum(function ($item) {
$qty = (float) ($item->qty ?? $item->quantity ?? 0);
$unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);

return $qty * $unitPrice;
});

$displayTotalAmount = $selectedOffer?->offer_total;

if (($displayTotalAmount === null || $displayTotalAmount === '') && $itemsTotal > 0) {
$displayTotalAmount = $itemsTotal;
}
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
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            padding: 24px;
        }

        .page {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
            border: 1px solid #d1d5db;
        }

        .print-actions {
            max-width: 1400px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border: 1px solid #111827;
            color: #111827;
            text-decoration: none;
            font-size: 14px;
            background: #fff;
        }

        .btn:hover {
            background: #111827;
            color: #fff;
        }

        .top-grid {
            display: grid;
            grid-template-columns: 1.1fr 1.15fr 1fr;
            border: 1px solid #111827;
            border-bottom: 0;
        }

        .box {
            border-right: 1px solid #111827;
            min-height: 136px;
        }

        .box:last-child {
            border-right: 0;
        }

        .box table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .box td {
            border-bottom: 1px solid #111827;
            padding: 6px 10px;
            vertical-align: top;
            font-size: 12px;
        }

        .box tr:last-child td {
            border-bottom: 0;
        }

        .label {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .center-box {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
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
            padding: 6px 8px;
            font-size: 12px;
            vertical-align: top;
        }

        .main-table th {
            text-transform: uppercase;
            text-align: center;
            font-weight: 700;
            line-height: 1.15;
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
            line-height: 1.45;
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
            border-right: 1px solid #111827;
            min-height: 180px;
        }

        .remarks-left .row-title {
            border-bottom: 1px solid #111827;
            padding: 8px 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .remarks-left .row-body {
            padding: 10px;
            min-height: 140px;
        }

        .remarks-right table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .remarks-right td {
            border-bottom: 1px solid #111827;
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: top;
        }

        .remarks-right tr:last-child td {
            border-bottom: 0;
        }

        .remarks-right .left-label {
            width: 48%;
            font-weight: 700;
            text-transform: uppercase;
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
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
        }

        .sign-name {
            margin-top: 26px;
            font-weight: 400;
            text-transform: none;
        }

        .sign-date {
            padding: 8px 10px;
            border-top: 1px solid #111827;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
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
                            <div>{{ $submittedDate }}</div>
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
                            <div class="label">Supplier</div>
                            <div>{{ $selectedOffer?->vendor?->name ?? $selectedOffer?->vendor_name ?? '-' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Purchase Requisition Number</div>
                            <div style="font-size: 18px; font-weight: 700; text-align: right;">
                                {{ $purchaseRequest->request_number ?? str_pad((string) $purchaseRequest->id, 4, '0', STR_PAD_LEFT) }}
                            </div>
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

                            @if (!empty($item->notes))
                            <div class="item-notes">{{ $cleanRichText($item->notes) }}</div>
                            @endif
                            @else
                            &nbsp;
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($i === 0)
                            {{ $bid1?->vendor?->name ?? $bid1?->vendor_name ?? '-' }}
                            @else
                            &nbsp;
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($i === 0)
                            {{ $bid2?->vendor?->name ?? $bid2?->vendor_name ?? '-' }}
                            @else
                            &nbsp;
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($i === 0)
                            {{ $bid3?->vendor?->name ?? $bid3?->vendor_name ?? '-' }}
                            @else
                            &nbsp;
                            @endif
                        </td>
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