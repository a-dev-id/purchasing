<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request Summary</title>

    <style>
        :root {
            --border: #1f2937;
            --muted: #6b7280;
            --bg: #f3f4f6;
            --white: #ffffff;
            --head: #e5e7eb;
            --soft: #f9fafb;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --info-bg: #dbeafe;
            --info-text: #1d4ed8;
            --warning-bg: #fef3c7;
            --warning-text: #92400e;
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
            --muted-bg: #e5e7eb;
            --muted-text: #374151;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-width: 0;
            overflow-x: auto;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .screen-actions {
            width: 1600px;
            max-width: none;
            margin: 24px auto 0;
            padding: 0 24px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: 1px solid var(--border);
            background: var(--white);
            color: #111827;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .page {
            width: 1600px;
            min-width: 1600px;
            max-width: none;
            margin: 14px auto 30px;
            background: var(--white);
            border: 1px solid var(--border);
            padding: 18px;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 28px;
            letter-spacing: 1px;
        }

        .header-meta {
            font-size: 13px;
            color: var(--muted);
        }

        .department-section {
            margin-top: 22px;
        }

        .department-section:first-of-type {
            margin-top: 0;
        }

        .department-heading {
            margin: 0 0 14px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            background: var(--soft);
        }

        .department-heading-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .department-heading-meta {
            font-size: 12px;
            color: var(--muted);
            margin: 0;
        }

        .pr-card {
            border: 1px solid var(--border);
            margin-bottom: 18px;
            page-break-inside: avoid;
            background: #fff;
            min-width: 1520px;
        }

        .pr-card-header {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
            align-items: center;
            min-width: 1520px;
        }

        .pr-number {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .pr-subtitle {
            font-size: 12px;
            color: var(--muted);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid var(--border);
            min-width: 1520px;
        }

        .summary-box {
            min-height: 66px;
            border-right: 1px solid var(--border);
            padding: 9px 10px;
        }

        .summary-box:last-child {
            border-right: 0;
        }

        .summary-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 6px;
        }

        .summary-value {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
        }

        .summary-value.normal {
            font-weight: 400;
        }

        .items-panel {
            padding: 10px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .items-panel::-webkit-scrollbar {
            height: 10px;
        }

        .items-panel::-webkit-scrollbar-track {
            background: #e5e7eb;
        }

        .items-panel::-webkit-scrollbar-thumb {
            background: #6b7280;
            border-radius: 999px;
        }

        .items-panel::-webkit-scrollbar-thumb:hover {
            background: #374151;
        }

        .panel-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            min-width: 1450px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            border: 1px solid var(--border);
            padding: 8px;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.35;
            word-wrap: break-word;
        }

        .items-table th {
            background: var(--head);
            text-align: center;
            font-weight: 700;
        }

        .col-item-no {
            width: 45px;
        }

        .col-item-image {
            width: 130px;
        }

        .col-item-desc {
            width: auto;
        }

        .col-item-qty {
            width: 90px;
        }

        .col-item-vendor {
            width: 220px;
        }

        .col-item-price {
            width: 160px;
        }

        .item-image-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            align-items: flex-start;
        }

        .image-button {
            padding: 0;
            margin: 0;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            width: 86px;
            height: 86px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .image-thumb {
            width: 84px;
            height: 84px;
            object-fit: cover;
            display: block;
            background: #fff;
        }

        .no-image {
            color: #6b7280;
            text-align: center;
            font-size: 12px;
            padding-top: 32px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            max-width: 100%;
        }

        .status-success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .status-info {
            background: var(--info-bg);
            color: var(--info-text);
        }

        .status-warning {
            background: var(--warning-bg);
            color: var(--warning-text);
        }

        .status-danger {
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        .status-muted {
            background: var(--muted-bg);
            color: var(--muted-text);
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: var(--muted);
        }

        .image-modal {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.82);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 9999;
        }

        .image-modal.is-open {
            display: flex;
        }

        .image-modal-content {
            position: relative;
            max-width: 92vw;
            max-height: 92vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-modal img {
            max-width: 92vw;
            max-height: 92vh;
            object-fit: contain;
            background: #fff;
            border: 2px solid #fff;
        }

        .image-modal-close {
            position: absolute;
            top: -14px;
            right: -14px;
            width: 34px;
            height: 34px;
            border: 1px solid #111827;
            border-radius: 999px;
            background: #fff;
            color: #111827;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media screen and (max-width: 1600px) {

            .screen-actions,
            .page {
                margin-left: 0;
                margin-right: 0;
            }
        }

        @media print {

            html,
            body {
                overflow: visible;
            }

            body {
                background: #fff;
            }

            .screen-actions,
            .image-modal {
                display: none !important;
            }

            .page {
                width: 100%;
                min-width: 0;
                max-width: none;
                margin: 0;
                border: none;
                padding: 0;
            }

            .department-section,
            .pr-card {
                page-break-inside: avoid;
            }

            .pr-card,
            .pr-card-header,
            .summary-grid {
                min-width: 0;
            }

            .items-panel {
                overflow: visible;
            }

            .items-table {
                width: 100%;
                min-width: 0;
            }

            .image-button {
                width: 76px;
                height: 76px;
                cursor: default;
            }

            .image-thumb {
                width: 74px;
                height: 74px;
            }

            a {
                text-decoration: none;
                color: inherit;
            }
        }

    </style>
</head>

<body>
    @php
    $groupedRows = collect($rows ?? [])
    ->groupBy(fn ($row) => trim((string) ($row['department_name'] ?? '')) ?: 'No Department');

    $cleanImages = function ($images) {
    return collect($images ?? [])
    ->filter(fn ($url) => filled($url))
    ->values();
    };

    $extractQty = function ($line, $item = []) {
    foreach (['qty', 'quantity', 'requested_qty', 'item_qty'] as $qtyKey) {
    if (isset($item[$qtyKey]) && is_numeric($item[$qtyKey])) {
    return (float) $item[$qtyKey];
    }
    }

    if (is_string($line) && preg_match('/Qty\s*:\s*([0-9]+(?:\.[0-9]+)?)/i', $line, $match)) {
    return (float) $match[1];
    }

    return 1;
    };

    $formatQty = function ($qty) {
    if ($qty === null || $qty === '') {
    return '-';
    }

    return fmod((float) $qty, 1.0) === 0.0
    ? number_format((float) $qty, 0)
    : number_format((float) $qty, 2);
    };

    $formatMoney = function ($amount, $currency = 'IDR') {
    if ($amount === null || $amount === '') {
    return null;
    }

    if (is_numeric($amount)) {
    return strtoupper($currency ?: 'IDR') . ' ' . number_format((float) $amount, 0, ',', '.');
    }

    return $amount;
    };

    $normaliseItems = function ($row) use ($cleanImages, $extractQty, $formatMoney) {
    $rowImages = $cleanImages($row['image_urls'] ?? []);
    $rawItems = collect($row['items'] ?? []);

    $getSelectedOffer = function ($item) {
    $offers = collect(
    data_get($item, 'vendor_offers') ??
    data_get($item, 'vendorOffers') ??
    data_get($item, 'item_vendor_offers') ??
    data_get($item, 'itemVendorOffers') ??
    data_get($item, 'offers') ??
    []
    );

    if ($offers->isEmpty()) {
    return null;
    }

    $selectedOffer = $offers->first(function ($offer) {
    return (bool) (
    data_get($offer, 'is_selected_by_accounting') ??
    data_get($offer, 'is_selected') ??
    data_get($offer, 'selected') ??
    false
    );
    });

    return $selectedOffer ?: $offers->first();
    };

    if ($rawItems->isNotEmpty()) {
    return $rawItems->map(function ($item, $index) use ($cleanImages, $extractQty, $rowImages, $getSelectedOffer, $formatMoney) {
    $description =
    data_get($item, 'article_description') ??
    data_get($item, 'description') ??
    data_get($item, 'item_description') ??
    data_get($item, 'item_name') ??
    data_get($item, 'name') ??
    '-';

    $itemImages = $cleanImages(
    data_get($item, 'image_urls') ??
    data_get($item, 'images') ??
    [
    data_get($item, 'image_url'),
    data_get($item, 'photo_url'),
    ]
    );

    if ($itemImages->isEmpty() && $rowImages->has($index)) {
    $itemImages = collect([$rowImages->get($index)])->filter()->values();
    }

    $selectedOffer = $getSelectedOffer($item);

    $selectedVendor = null;
    $selectedPrice = null;

    if ($selectedOffer) {
    $selectedVendor =
    data_get($selectedOffer, 'vendor_name') ??
    data_get($selectedOffer, 'selected_vendor') ??
    data_get($selectedOffer, 'selected_vendor_name') ??
    data_get($selectedOffer, 'vendor.name') ??
    data_get($selectedOffer, 'name');

    $selectedPrice = $formatMoney(
    data_get($selectedOffer, 'offer_total') ??
    data_get($selectedOffer, 'total_price') ??
    data_get($selectedOffer, 'price') ??
    data_get($selectedOffer, 'amount'),
    data_get($selectedOffer, 'currency') ?? 'IDR'
    );
    }

    return [
    'description' => $description,
    'qty' => $extractQty($description, is_array($item) ? $item : []),
    'unit' => data_get($item, 'unit') ?? data_get($item, 'unit_name') ?? '',
    'image_urls' => $itemImages,

    'selected_vendor' =>
    $selectedVendor ??
    data_get($item, 'selected_vendor') ??
    data_get($item, 'selected_vendor_name') ??
    null,

    'selected_price' =>
    $selectedPrice ??
    data_get($item, 'selected_price_display') ??
    data_get($item, 'selected_total_price_display') ??
    data_get($item, 'price_display') ??
    data_get($item, 'selected_price') ??
    null,
    ];
    })->values();
    }

    $itemLines = collect($row['article_description'] ?? []);

    return $itemLines->map(function ($itemLine, $index) use ($rowImages, $extractQty) {
    $itemImages = collect();

    if ($rowImages->has($index)) {
    $itemImages = collect([$rowImages->get($index)])->filter()->values();
    }

    return [
    'description' => $itemLine,
    'qty' => $extractQty($itemLine),
    'unit' => '',
    'image_urls' => $itemImages,
    'selected_vendor' => null,
    'selected_price' => null,
    ];
    })->values();
    };
    @endphp

    <div class="screen-actions">
        <button type="button" class="btn" onclick="closeSummaryTab()">Back</button>
        <button type="button" class="btn" onclick="window.print()">Print</button>
    </div>

    <div class="page">
        <div class="header">
            <h1>PURCHASE REQUEST SUMMARY</h1>
            <div class="header-meta">
                Generated at {{ $generatedAt->format('d M Y H:i') }}
            </div>
        </div>

        @forelse ($groupedRows as $departmentName => $departmentRows)
        <div class="department-section">
            <div class="department-heading">
                <div class="department-heading-title">{{ strtoupper($departmentName) }}</div>
                <p class="department-heading-meta">
                    Total PR: {{ $departmentRows->count() }}
                </p>
            </div>

            @foreach ($departmentRows as $row)
            @php
            $prStatusText =
            $row['desk_label'] ??
            $row['current_desk'] ??
            $row['desk'] ??
            $row['status_label'] ??
            '-';

            $prStatusText = preg_replace('/^to\s+/i', '', (string) $prStatusText);

            $prStatusClass =
            $row['desk_class'] ??
            $row['current_desk_class'] ??
            $row['status_class'] ??
            'status-muted';

            $submittedForDays = $row['submitted_at_raw'] ?? $row['submitted_at'] ?? null;
            $receivedForDays = $row['received_at_raw'] ?? $row['received_at'] ?? null;

            $totalDaysDisplay = $row['total_days'] ?? null;

            if (
            ($totalDaysDisplay === null || $totalDaysDisplay === '' || $totalDaysDisplay === '-') &&
            filled($submittedForDays) &&
            filled($receivedForDays) &&
            $receivedForDays !== '-'
            ) {
            try {
            $submittedDate = \Carbon\Carbon::parse($submittedForDays)->startOfDay();
            $receivedDate = \Carbon\Carbon::parse($receivedForDays)->startOfDay();
            $totalDaysDisplay = $submittedDate->diffInDays($receivedDate);
            } catch (\Throwable $e) {
            $totalDaysDisplay = '-';
            }
            }

            if ($totalDaysDisplay === null || $totalDaysDisplay === '') {
            $totalDaysDisplay = '-';
            }

            $items = $normaliseItems($row);

            $totalQty = $items->sum(fn ($item) => is_numeric($item['qty'] ?? null) ? (float) $item['qty'] : 0);
            $totalQtyDisplay = $totalQty > 0 ? $formatQty($totalQty) : $items->count();

            $selectedVendor =
            $row['selected_vendor'] ??
            $row['selected_vendor_name'] ??
            $row['vendor_summary'] ??
            '-';

            $totalPrice =
            $row['selected_total_price_display'] ??
            $row['total_price_display'] ??
            $row['price_display'] ??
            '-';
            @endphp

            <div class="pr-card">
                <div class="pr-card-header">
                    <div>
                        <div class="pr-number">{{ $row['request_number'] }}</div>
                        <div class="pr-subtitle">
                            Requester: {{ $row['requester_name'] ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="summary-label">Date Submit</div>
                        <div class="summary-value normal">{{ $row['submitted_at'] ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="summary-label">Date Needed</div>
                        <div class="summary-value normal">{{ $row['date_needed'] ?? '-' }}</div>
                    </div>

                    <div class="text-center">
                        <span class="status-badge {{ $row['priority_class'] ?? 'status-muted' }}">
                            {{ $row['priority_label'] ?? '-' }}
                        </span>

                        <span class="status-badge {{ $prStatusClass }}" style="margin-left: 6px;">
                            {{ $prStatusText }}
                        </span>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-box">
                        <div class="summary-label">Purpose</div>
                        <div class="summary-value normal">{{ $row['request_name'] ?? '-' }}</div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-label">Remark</div>
                        <div class="summary-value normal">{{ $row['purpose'] ?? '-' }}</div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-label">Items Requested</div>
                        <div class="summary-value">
                            {{ $items->count() }} item{{ $items->count() === 1 ? '' : 's' }}
                            <br>
                            <span class="muted">Total Qty: {{ $totalQtyDisplay }}</span>
                        </div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-label">Received / Total Days</div>
                        <div class="summary-value normal">
                            {{ $row['received_at'] ?? '-' }}
                            <br>
                            <span class="muted">{{ $totalDaysDisplay }} day{{ (string) $totalDaysDisplay === '1' ? '' : 's' }}</span>
                        </div>
                    </div>
                </div>

                <div class="items-panel">
                    <div class="panel-title">Items Requested</div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="col-item-no">NO</th>
                                <th class="col-item-image">IMAGE</th>
                                <th class="col-item-desc">ARTICLE & DESCRIPTION</th>
                                <th class="col-item-qty">QTY</th>
                                <th class="col-item-vendor">SELECTED VENDOR</th>
                                <th class="col-item-price">PRICE</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($items as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>

                                <td class="text-center">
                                    @if (collect($item['image_urls'] ?? [])->isNotEmpty())
                                    <div class="item-image-wrap">
                                        @foreach (collect($item['image_urls'] ?? [])->take(3) as $imageUrl)
                                        <button type="button" class="image-button" onclick="openImageModal(@js($imageUrl))">
                                            <img src="{{ $imageUrl }}" alt="Item Image" class="image-thumb">
                                        </button>
                                        @endforeach
                                    </div>
                                    @else
                                    <div class="no-image">-</div>
                                    @endif
                                </td>

                                <td>{{ $item['description'] ?? '-' }}</td>

                                <td class="text-center">
                                    {{ $formatQty($item['qty'] ?? null) }}
                                    @if (filled($item['unit'] ?? null))
                                    {{ $item['unit'] }}
                                    @endif
                                </td>

                                <td>
                                    {{ filled($item['selected_vendor'] ?? null) ? $item['selected_vendor'] : '-' }}
                                </td>

                                <td class="text-right">
                                    {{ filled($item['selected_price'] ?? null) ? $item['selected_price'] : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No items found.</td>
                            </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">TOTAL REQUESTED</th>
                                <th class="text-center">{{ $totalQtyDisplay }}</th>
                                <th>{{ filled($selectedVendor) ? $selectedVendor : '-' }}</th>
                                <th class="text-right">{{ filled($totalPrice) ? $totalPrice : '-' }}</th>
                            </tr>
                        </tfoot>
                    </table>

                    @if (filled($row['remarks'] ?? null))
                    <div style="margin-top: 10px; font-size: 12px;">
                        <strong>Workflow Remarks:</strong>
                        {{ $row['remarks'] }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @empty
        <div class="pr-card">
            <div class="items-panel">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>MESSAGE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">No purchase requests found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endforelse
    </div>

    <div id="imageModal" class="image-modal" onclick="closeImageModal(event)">
        <div class="image-modal-content">
            <button type="button" class="image-modal-close" onclick="closeImageModal(event)">&times;</button>
            <img id="imageModalPreview" src="" alt="Preview">
        </div>
    </div>

    <script>
        function closeSummaryTab() {
            window.close();

            setTimeout(function() {
                if (!window.closed) {
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = '/purchasing/purchase-requests';
                    }
                }
            }, 100);
        }

        function openImageModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const preview = document.getElementById('imageModalPreview');

            preview.src = imageUrl;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal(event) {
            const modal = document.getElementById('imageModal');
            const content = modal.querySelector('.image-modal-content');

            if (event && content.contains(event.target) && !event.target.classList.contains('image-modal-close')) {
                return;
            }

            modal.classList.remove('is-open');
            document.getElementById('imageModalPreview').src = '';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('imageModal');

                if (modal.classList.contains('is-open')) {
                    modal.classList.remove('is-open');
                    document.getElementById('imageModalPreview').src = '';
                    document.body.style.overflow = '';
                }
            }
        });
    </script>
</body>

</html>