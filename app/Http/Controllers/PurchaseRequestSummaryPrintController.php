<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class PurchaseRequestSummaryPrintController extends Controller
{
    public function index(): View
    {
        $rows = PurchaseRequest::query()
            ->with([
                'requester',
                'items.photos',
                'items.item.photos',
                'items.vendorOffers.vendor',
                'vendorOffers',
                'logs',
            ])
            ->where('status', '!=', 'draft')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (PurchaseRequest $purchaseRequest) {
                return [
                    'request_number' => $purchaseRequest->request_number ?: ('PR-' . $purchaseRequest->id),
                    'department_name' => $purchaseRequest->department_name ?: '-',
                    'requester_name' => $purchaseRequest->requester_name
                        ?: optional($purchaseRequest->requester)->name
                        ?: '-',

                    'request_name' => $this->requestName($purchaseRequest),

                    'article_description' => $this->itemsSummary($purchaseRequest),
                    'image_urls' => $this->imageUrls($purchaseRequest),
                    'items' => $this->itemRows($purchaseRequest),

                    'purpose' => $this->plainText($purchaseRequest->request_notes),

                    'submitted_at_raw' => $purchaseRequest->submitted_at?->toDateTimeString(),
                    'submitted_at' => $purchaseRequest->submitted_at?->format('d M Y H:i') ?: '-',

                    'date_needed' => $purchaseRequest->date_needed?->format('d M Y') ?: '-',
                    'priority_label' => $this->priorityLabel($purchaseRequest->priority),
                    'priority_class' => $this->priorityClass($purchaseRequest->priority),

                    'status_label' => $this->statusLabel($purchaseRequest->status),
                    'status_class' => $this->statusClass($purchaseRequest->status),
                    'desk_label' => $this->deskLabel($purchaseRequest->status),
                    'desk_class' => $this->deskClass($purchaseRequest->status),

                    'vendor_summary' => $this->vendorSummary($purchaseRequest),
                    'price_display' => $this->currency($this->selectedTotal($purchaseRequest)),

                    'received_at_raw' => $purchaseRequest->received_at?->toDateTimeString(),
                    'received_at' => $purchaseRequest->received_at?->format('d M Y H:i') ?: '-',

                    'total_days' => $this->totalDaysToReceive($purchaseRequest),
                    'remarks' => $this->latestRemark($purchaseRequest),
                ];
            });

        return view('purchase-requests.summary-print', [
            'rows' => $rows,
            'generatedAt' => now(),
        ]);
    }

    protected function itemRows(PurchaseRequest $purchaseRequest): array
    {
        return collect($purchaseRequest->items ?? [])
            ->map(function ($item) {
                $name = trim((string) (
                    $item->item_name
                    ?: optional($item->item)->name
                    ?: '-'
                ));

                $rawSpecification = (string) (
                    $item->specification
                    ?: optional($item->item)->default_specification
                    ?: ''
                );

                $specification = html_entity_decode(strip_tags($rawSpecification), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $specification = preg_replace('/\s+/', ' ', $specification ?? '');
                $specification = trim((string) $specification);

                $qty = $item->qty ?? $item->quantity ?? null;
                $qtyNumber = is_numeric($qty) ? (float) $qty : 1;

                $qtyDisplay = null;

                if ($qty !== null && $qty !== '') {
                    $qtyDisplay = rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');
                }

                $parts = [$name];

                if ($specification !== '') {
                    $parts[] = $specification;
                }

                if ($qtyDisplay !== null) {
                    $parts[] = 'Qty: ' . $qtyDisplay;
                }

                $selectedOffer = collect($item->vendorOffers ?? [])
                    ->firstWhere('is_selected_by_accounting', true);

                $photoPaths = collect($item->photos ?? [])->pluck('file_path');

                if ($photoPaths->isEmpty()) {
                    $photoPaths = collect(optional($item->item)->photos ?? [])->pluck('file_path');
                }

                $unitPrice = $selectedOffer?->offer_total !== null
                    ? (float) $selectedOffer->offer_total
                    : null;

                $totalPrice = $unitPrice !== null
                    ? $unitPrice * $qtyNumber
                    : null;

                return [
                    'item_name' => $name,
                    'description' => implode(' | ', $parts),
                    'article_description' => implode(' | ', $parts),

                    'qty' => $qty,
                    'unit' => $item->unit ?: '',

                    'image_urls' => $photoPaths
                        ->filter()
                        ->map(fn($path) => $this->imagePathToUrl($path))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),

                    'selected_vendor' => $selectedOffer
                        ? (
                            $selectedOffer->vendor_name
                            ?: optional($selectedOffer->vendor)->name
                            ?: null
                        )
                        : null,

                    'selected_vendor_name' => $selectedOffer
                        ? (
                            $selectedOffer->vendor_name
                            ?: optional($selectedOffer->vendor)->name
                            ?: null
                        )
                        : null,

                    'selected_price' => $totalPrice !== null
                        ? $this->currency($totalPrice)
                        : null,

                    'selected_price_display' => $totalPrice !== null
                        ? $this->currency($totalPrice)
                        : null,

                    'unit_price' => $unitPrice !== null
                        ? $this->currency($unitPrice)
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function requestName(PurchaseRequest $purchaseRequest): string
    {
        $title = trim((string) ($purchaseRequest->title ?? $purchaseRequest->request_name ?? ''));

        if ($title !== '') {
            return $title;
        }

        $itemNames = collect($purchaseRequest->items ?? [])
            ->map(function ($item) {
                return trim((string) (
                    $item->item_name
                    ?: optional($item->item)->name
                    ?: ''
                ));
            })
            ->filter()
            ->unique()
            ->values();

        return $itemNames->isNotEmpty()
            ? $itemNames->implode(', ')
            : '-';
    }

    protected function imageUrls(PurchaseRequest $purchaseRequest): array
    {
        return collect($purchaseRequest->items ?? [])
            ->flatMap(function ($item) {
                $prItemPhotos = collect($item->photos ?? [])->pluck('file_path');

                if ($prItemPhotos->isNotEmpty()) {
                    return $prItemPhotos;
                }

                return collect(optional($item->item)->photos ?? [])->pluck('file_path');
            })
            ->filter()
            ->map(fn($path) => $this->imagePathToUrl($path))
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    protected function imagePathToUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }

    protected function totalDaysToReceive(PurchaseRequest $purchaseRequest): string
    {
        $start = $purchaseRequest->submitted_at ?? $purchaseRequest->created_at;

        if (! $start) {
            return '-';
        }

        $receivedStatuses = [
            'received_by_requester',
            'received_by_requester_by_fc',
            'approved',
        ];

        $end = $purchaseRequest->received_at;

        if (! $end && in_array((string) $purchaseRequest->status, $receivedStatuses, true)) {
            $end = $purchaseRequest->current_status_at ?? $purchaseRequest->updated_at;
        }

        if (! $end) {
            $end = now();
        }

        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());

        return (string) max($days, 0);
    }

    protected function priorityLabel(?string $state): string
    {
        return match ($state) {
            'urgent' => 'Urgent',
            'normal' => 'Normal',
            default => '-',
        };
    }

    protected function priorityClass(?string $state): string
    {
        return match ($state) {
            'urgent' => 'status-danger',
            'normal' => 'status-info',
            default => 'status-muted',
        };
    }

    protected function itemsSummary(PurchaseRequest $purchaseRequest): array
    {
        $items = collect($purchaseRequest->items ?? []);

        if ($items->isEmpty()) {
            return [$purchaseRequest->title ?: '-'];
        }

        return $items
            ->map(function ($item) {
                $name = trim((string) (
                    $item->item_name
                    ?: optional($item->item)->name
                    ?: '-'
                ));

                $rawSpecification = (string) (
                    $item->specification
                    ?: optional($item->item)->default_specification
                    ?: ''
                );

                $specification = html_entity_decode(strip_tags($rawSpecification), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $specification = preg_replace('/\s+/', ' ', $specification ?? '');
                $specification = trim((string) $specification);

                $qty = $item->qty ?? $item->quantity ?? null;

                $parts = [$name];

                if ($specification !== '') {
                    $parts[] = $specification;
                }

                if ($qty !== null && $qty !== '') {
                    $qty = rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');
                    $parts[] = 'Qty: ' . $qty;
                }

                return implode(' | ', $parts);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function vendorSummary(PurchaseRequest $purchaseRequest): string
    {
        $selectedPrVendor = collect($purchaseRequest->vendorOffers ?? [])
            ->firstWhere('is_selected_by_accounting', true);

        if ($selectedPrVendor && filled($selectedPrVendor->vendor_name)) {
            return trim((string) $selectedPrVendor->vendor_name);
        }

        $selectedItemVendors = collect($purchaseRequest->items ?? [])
            ->flatMap(function ($item) {
                return collect($item->vendorOffers ?? [])
                    ->where('is_selected_by_accounting', true);
            })
            ->pluck('vendor_name')
            ->filter()
            ->map(fn($name) => trim((string) $name))
            ->unique()
            ->values();

        if ($selectedItemVendors->isNotEmpty()) {
            return $selectedItemVendors->implode(', ');
        }

        return '-';
    }

    protected function selectedTotal(PurchaseRequest $purchaseRequest): ?float
    {
        if (($purchaseRequest->vendor_comparison_mode ?? 'item') === 'pr') {
            $selectedPrVendor = collect($purchaseRequest->vendorOffers ?? [])
                ->firstWhere('is_selected_by_accounting', true);

            return $selectedPrVendor?->offer_total !== null
                ? (float) $selectedPrVendor->offer_total
                : null;
        }

        $selectedItemTotal = collect($purchaseRequest->items ?? [])
            ->sum(function ($item) {
                $selectedOffer = collect($item->vendorOffers ?? [])
                    ->firstWhere('is_selected_by_accounting', true);

                if (! $selectedOffer || $selectedOffer->offer_total === null) {
                    return 0;
                }

                $qty = is_numeric($item->qty ?? null)
                    ? (float) $item->qty
                    : 1;

                return (float) $selectedOffer->offer_total * $qty;
            });

        return $selectedItemTotal > 0 ? (float) $selectedItemTotal : null;
    }

    protected function latestRemark(PurchaseRequest $purchaseRequest): string
    {
        if (in_array((string) $purchaseRequest->status, ['on_shipping', 'on_shipping_by_fc'], true)) {
            return 'The PR is Paid to vendor by Financial Controller and On Shipping.';
        }

        $log = collect($purchaseRequest->logs ?? [])
            ->filter(fn($log) => filled($log->message))
            ->sortByDesc(fn($log) => $log->acted_at ?? $log->created_at)
            ->first();

        if ($log && filled($log->message)) {
            return $this->plainText($log->message);
        }

        return $this->plainText($purchaseRequest->request_notes);
    }

    protected function plainText(?string $value): string
    {
        $value = trim(strip_tags((string) $value));

        return $value !== '' ? $value : '-';
    }

    protected function currency(?float $amount): string
    {
        if ($amount === null) {
            return '-';
        }

        return 'IDR ' . number_format($amount, 0, ',', '.');
    }

    protected function statusLabel(?string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'submitted' => 'To Purchasing',
            'revision_from_purchasing' => 'Back to Requester',
            'submitted_to_accounting' => 'To Accounting',
            'on_hold_by_accounting' => 'Hold Accounting',
            'revision_from_accounting' => 'Back to Requester',
            'revision_to_purchasing_from_accounting' => 'Back to Purchasing',
            'revision_to_requester_from_accounting' => 'Back to Requester',
            'submitted_to_gm' => 'To GM',
            'on_hold_by_gm' => 'Hold GM',
            'revision_from_gm' => 'Back to Requester',
            'revision_to_purchasing_from_gm' => 'Back to Purchasing',
            'revision_to_accounting_from_gm' => 'Back to Accounting',
            'revision_to_requester_from_gm' => 'Back to Requester',
            'gm_approved' => 'New from GM',
            'pending', 'pending_by_fc' => 'Pending',
            'on_progress', 'on_progress_by_fc' => 'On Progress',
            'waiting_payment', 'waiting_payment_by_fc' => 'Waiting Payment',
            'paid_to_vendor', 'paid_to_vendor_by_fc' => 'Paid to Vendor',
            'on_shipping', 'on_shipping_by_fc', 'item_arrived_by_fc' => 'On Shipping',
            'on_hold_by_fc' => 'On Hold',
            'received_by_requester', 'received_by_requester_by_fc', 'approved' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => str($state)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function statusClass(?string $state): string
    {
        return match ($state) {
            'received_by_requester',
            'received_by_requester_by_fc',
            'approved',
            'paid_to_vendor',
            'paid_to_vendor_by_fc' => 'status-success',

            'on_progress',
            'on_progress_by_fc',
            'on_shipping',
            'on_shipping_by_fc',
            'item_arrived_by_fc' => 'status-info',

            'submitted',
            'submitted_to_accounting',
            'submitted_to_gm',
            'gm_approved',
            'waiting_payment',
            'waiting_payment_by_fc' => 'status-warning',

            'cancelled',
            'rejected' => 'status-danger',

            'pending',
            'pending_by_fc',
            'on_hold_by_fc',
            'on_hold_by_accounting',
            'on_hold_by_gm' => 'status-muted',

            default => 'status-muted',
        };
    }

    protected function deskLabel(?string $state): string
    {
        return match ($state) {
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm' => 'Requester',

            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'paid_to_vendor',
            'paid_to_vendor_by_fc',
            'on_shipping',
            'on_shipping_by_fc' => 'Purchasing',

            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm' => 'Accounting',

            'submitted_to_gm',
            'on_hold_by_gm' => 'GM',

            'gm_approved',
            'pending',
            'pending_by_fc',
            'on_progress',
            'on_progress_by_fc',
            'waiting_payment',
            'waiting_payment_by_fc',
            'item_arrived_by_fc',
            'on_hold_by_fc' => 'Financial Controller',

            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'Done',

            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',

            default => '-',
        };
    }

    protected function deskClass(?string $state): string
    {
        return match ($state) {
            'received_by_requester',
            'received_by_requester_by_fc',
            'approved' => 'status-success',

            'submitted',
            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm',
            'submitted_to_accounting',
            'on_hold_by_accounting',
            'revision_to_accounting_from_gm',
            'submitted_to_gm',
            'on_hold_by_gm',
            'gm_approved',
            'pending',
            'pending_by_fc',
            'on_progress',
            'on_progress_by_fc',
            'waiting_payment',
            'waiting_payment_by_fc',
            'paid_to_vendor',
            'paid_to_vendor_by_fc',
            'on_shipping',
            'on_shipping_by_fc',
            'item_arrived_by_fc',
            'on_hold_by_fc' => 'status-warning',

            'cancelled',
            'rejected' => 'status-danger',

            default => 'status-muted',
        };
    }
}
