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
                'items.vendorOffers',
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
                    'article_description' => $this->itemsSummary($purchaseRequest),
                    'image_urls' => $this->imageUrls($purchaseRequest),
                    'purpose' => $this->plainText($purchaseRequest->request_notes),
                    'submitted_at' => $purchaseRequest->submitted_at?->format('d M Y') ?: '-',
                    'date_needed' => $purchaseRequest->date_needed?->format('d M Y') ?: '-',
                    'priority_label' => $this->priorityLabel($purchaseRequest->priority),
                    'priority_class' => $this->priorityClass($purchaseRequest->priority),
                    'status_label' => $this->statusLabel($purchaseRequest->status),
                    'status_class' => $this->statusClass($purchaseRequest->status),
                    'vendor_summary' => $this->vendorSummary($purchaseRequest),
                    'price_display' => $this->currency($this->selectedTotal($purchaseRequest)),
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

    protected function imageUrls(PurchaseRequest $purchaseRequest): array
    {
        return collect($purchaseRequest->items ?? [])
            ->flatMap(function ($item) {
                return collect($item->photos ?? [])->pluck('file_path');
            })
            ->filter()
            ->map(function ($path) {
                $path = trim((string) $path);

                if ($path === '') {
                    return null;
                }

                return str_starts_with($path, 'http')
                    ? $path
                    : Storage::url($path);
            })
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    protected function totalDaysToReceive(PurchaseRequest $purchaseRequest): string
    {
        $start = $purchaseRequest->created_at;
        $end = $purchaseRequest->received_at;

        if (! $start || ! $end) {
            return '-';
        }

        $days = $start->startOfDay()->diffInDays($end->startOfDay());

        return $days . ' day' . ($days === 1 ? '' : 's');
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
                $name = trim((string) ($item->item_name ?: $item->name ?: '-'));

                $qty = $item->qty ?? $item->quantity ?? null;

                if ($qty !== null && $qty !== '') {
                    $qty = rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');

                    return $name . ' | Qty: ' . $qty;
                }

                return $name;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function vendorSummary(PurchaseRequest $purchaseRequest): string
    {
        // PR mode: show only the selected winning vendor
        $selectedPrVendor = collect($purchaseRequest->vendorOffers ?? [])
            ->firstWhere('is_selected_by_accounting', true);

        if ($selectedPrVendor && filled($selectedPrVendor->vendor_name)) {
            return trim((string) $selectedPrVendor->vendor_name);
        }

        // Item mode: show only selected winning vendors from each item
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

        // No winner selected yet
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
            ->flatMap(function ($item) {
                return collect($item->vendorOffers ?? [])->where('is_selected_by_accounting', true);
            })
            ->sum(function ($offer) {
                return (float) ($offer->offer_total ?? 0);
            });

        return $selectedItemTotal > 0 ? (float) $selectedItemTotal : null;
    }

    protected function latestRemark(PurchaseRequest $purchaseRequest): string
    {
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
}
