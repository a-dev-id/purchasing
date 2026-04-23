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

        body {
            margin: 0;
            background: var(--bg);
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .screen-actions {
            max-width: 2050px;
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
            max-width: 2050px;
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
            margin-top: 18px;
        }

        .department-section:first-of-type {
            margin-top: 0;
        }

        .department-heading {
            margin: 0 0 10px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            background: #f9fafb;
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

        .table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            border: 1px solid var(--border);
        }

        .table-scroll table {
            width: 100%;
            min-width: 2050px;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0;
        }

        .table-scroll::-webkit-scrollbar {
            height: 10px;
        }

        .table-scroll::-webkit-scrollbar-track {
            background: #e5e7eb;
        }

        .table-scroll::-webkit-scrollbar-thumb {
            background: #6b7280;
            border-radius: 999px;
        }

        .table-scroll::-webkit-scrollbar-thumb:hover {
            background: #374151;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        th,
        td {
            border: 1px solid var(--border);
            padding: 8px 8px;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.35;
            word-wrap: break-word;
        }

        th {
            background: var(--head);
            text-align: center;
            font-weight: 700;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        .col-no {
            width: 9%;
        }

        .col-requester {
            width: 9%;
        }

        .col-article {
            width: 15%;
        }

        .col-images {
            width: 11%;
        }

        .col-request-name {
            width: 12%;
        }

        .col-purpose {
            width: 12%;
        }

        .col-submit {
            width: 7%;
        }

        .col-needed {
            width: 7%;
        }

        .col-priority {
            width: 6%;
        }

        .col-status {
            width: 11%;
        }

        .col-vendor {
            width: 10%;
        }

        .col-price {
            width: 8%;
        }

        .col-received {
            width: 8%;
        }

        .col-total-days {
            width: 7%;
        }

        .col-remarks {
            width: 13%;
        }

        .item-list {
            margin: 0;
            padding-left: 16px;
        }

        .item-list li {
            margin-bottom: 4px;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(2, 60px);
            gap: 6px;
            justify-content: center;
            align-content: start;
            margin: 0 auto;
        }

        .image-button {
            padding: 0;
            margin: 0;
            border: 1px solid #d1d5db;
            background: #fff;
            cursor: pointer;
            width: 60px;
            height: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .image-thumb {
            width: 58px;
            height: 58px;
            object-fit: cover;
            display: block;
            background: #fff;
        }

        .no-image {
            color: #6b7280;
            text-align: center;
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

        @media print {
            body {
                background: #fff;
            }

            .screen-actions,
            .image-modal {
                display: none !important;
            }

            .page {
                max-width: none;
                margin: 0;
                border: none;
                padding: 0;
            }

            .department-section {
                page-break-inside: avoid;
            }

            .table-scroll {
                overflow: visible;
                border: 1px solid var(--border);
            }

            .table-scroll table {
                min-width: 0;
                width: 100%;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .image-button {
                border: 1px solid #d1d5db;
                cursor: default;
            }
        }

    </style>
</head>

<body>
    @php
    $groupedRows = collect($rows ?? [])
    ->groupBy(fn ($row) => trim((string) ($row['department_name'] ?? '')) ?: 'No Department');
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

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="col-no">NO PR</th>
                            <th class="col-requester">REQUESTER</th>
                            <th class="col-article">ARTICLE & DESCRIPTION</th>
                            <th class="col-images">IMAGES</th>
                            <th class="col-request-name">PURPOSE</th>
                            <th class="col-purpose">REMARK</th>
                            <th class="col-submit">DATE SUBMIT</th>
                            <th class="col-needed">DATE NEEDED</th>
                            <th class="col-priority">PRIORITY</th>
                            <th class="col-status">STATUS</th>
                            <th class="col-vendor">VENDOR</th>
                            <th class="col-price">PRICE</th>
                            <th class="col-received">RECEIVED AT</th>
                            <th class="col-total-days">TOTAL DAYS</th>
                            <th class="col-remarks">REMARKS</th>
                        </tr>
                    </thead>
                    <tbody>
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

                        $imageUrls = collect($row['image_urls'] ?? [])
                        ->filter(fn ($url) => filled($url))
                        ->take(4)
                        ->values();

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
                        @endphp
                        <tr>
                            <td>{{ $row['request_number'] }}</td>
                            <td>{{ $row['requester_name'] }}</td>
                            <td>
                                <ul class="item-list">
                                    @foreach ($row['article_description'] as $itemLine)
                                    <li>{{ $itemLine }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td style="vertical-align: top; text-align: center;">
                                @if ($imageUrls->isNotEmpty())
                                <div class="image-grid">
                                    @foreach ($imageUrls as $imageUrl)
                                    <button type="button" class="image-button" onclick="openImageModal('{{ $imageUrl }}')">
                                        <img src="{{ $imageUrl }}" alt="Item Image" class="image-thumb">
                                    </button>
                                    @endforeach
                                </div>
                                @else
                                <div class="no-image">-</div>
                                @endif
                            </td>
                            <td>{{ $row['request_name'] ?? '-' }}</td>
                            <td>{{ $row['purpose'] }}</td>
                            <td class="text-center">{{ $row['submitted_at'] }}</td>
                            <td class="text-center">{{ $row['date_needed'] }}</td>
                            <td class="text-center">
                                <span class="status-badge {{ $row['priority_class'] }}">
                                    {{ $row['priority_label'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="status-badge {{ $prStatusClass }}">
                                    {{ $prStatusText }}
                                </span>
                            </td>
                            <td>{{ $row['vendor_summary'] }}</td>
                            <td class="text-right">{{ $row['price_display'] }}</td>
                            <td class="text-center">{{ $row['received_at'] }}</td>
                            <td class="text-center">{{ $totalDaysDisplay }}</td>
                            <td>{{ $row['remarks'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="col-no">NO PR</th>
                        <th class="col-requester">REQUESTER</th>
                        <th class="col-article">ARTICLE</th>
                        <th class="col-images">IMAGES</th>
                        <th class="col-request-name">PURPOSE</th>
                        <th class="col-purpose">REMARK</th>
                        <th class="col-submit">DATE SUBMIT</th>
                        <th class="col-needed">DATE NEEDED</th>
                        <th class="col-priority">PRIORITY</th>
                        <th class="col-status">PR STATUS</th>
                        <th class="col-vendor">VENDOR</th>
                        <th class="col-price">PRICE</th>
                        <th class="col-received">RECEIVED AT</th>
                        <th class="col-total-days">TOTAL DAYS</th>
                        <th class="col-remarks">REMARKS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="15" class="text-center">No purchase requests found.</td>
                    </tr>
                </tbody>
            </table>
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