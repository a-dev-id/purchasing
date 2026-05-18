<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseRequestGmController extends Controller
{
    public function approveItems(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isGmUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_gm') {
            return back()->with('error', 'This purchase request is not waiting for GM approval.');
        }

        $validated = $request->validate([
            'approved_items' => ['nullable', 'array'],
            'approved_items.*' => ['integer', 'exists:purchase_request_items,id'],
            'deferred_until' => ['nullable', 'date'],
            'not_approved_reasons' => ['nullable', 'array'],
            'not_approved_reasons.*' => ['nullable', 'string', 'in:Reason,Canceled'],
            'not_approved_reason_details' => ['nullable', 'array'],
            'not_approved_reason_details.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $purchaseRequest->load([
            'items.vendorOffers',
        ]);

        $approvedItemIds = collect($validated['approved_items'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($approvedItemIds->isEmpty()) {
            return back()
                ->withErrors([
                    'approved_items' => 'Please approve at least one item.',
                ])
                ->withInput();
        }

        $allItemIds = $purchaseRequest->items
            ->pluck('id')
            ->map(fn($id) => (int) $id);

        $invalidItemIds = $approvedItemIds->diff($allItemIds);

        if ($invalidItemIds->isNotEmpty()) {
            return back()
                ->withErrors([
                    'approved_items' => 'Invalid item selection.',
                ])
                ->withInput();
        }

        foreach ($purchaseRequest->items->whereIn('id', $approvedItemIds) as $item) {
            $hasSelectedVendor = $item->vendorOffers->contains('is_selected_by_accounting', true);

            if (! $hasSelectedVendor) {
                return back()
                    ->withErrors([
                        'approved_items' => "Selected item {$item->item_name} does not have a selected vendor.",
                    ])
                    ->withInput();
            }
        }

        $notApprovedItems = $purchaseRequest->items
            ->whereNotIn('id', $approvedItemIds);

        $reasonItemIds = collect();
        $canceledItemIds = collect();
        $reasonDetails = [];

        foreach ($notApprovedItems as $item) {
            $reason = $request->input("not_approved_reasons.{$item->id}");

            if (! in_array($reason, ['Reason', 'Canceled'], true)) {
                return back()
                    ->withErrors([
                        "not_approved_reasons.{$item->id}" => "Please select a reason for item: {$item->item_name}.",
                    ])
                    ->withInput();
            }

            if ($reason === 'Reason') {
                $detail = trim((string) $request->input("not_approved_reason_details.{$item->id}"));

                if ($detail === '') {
                    return back()
                        ->withErrors([
                            "not_approved_reason_details.{$item->id}" => "Please input the reason detail for item: {$item->item_name}.",
                        ])
                        ->withInput();
                }

                $reasonItemIds->push((int) $item->id);
                $reasonDetails[(int) $item->id] = $detail;
            }

            if ($reason === 'Canceled') {
                $canceledItemIds->push((int) $item->id);
            }
        }

        if ($reasonItemIds->isNotEmpty() && empty($validated['deferred_until'])) {
            return back()
                ->withErrors([
                    'deferred_until' => 'Deferred purchase date is required when there are items moved to child PR.',
                ])
                ->withInput();
        }

        DB::transaction(function () use (
            $purchaseRequest,
            $approvedItemIds,
            $reasonItemIds,
            $canceledItemIds,
            $reasonDetails,
            $validated
        ) {
            $purchaseRequest->load('items');

            $fromStatus = $purchaseRequest->status;
            $childPurchaseRequest = null;

            $approvedItems = $purchaseRequest->items
                ->whereIn('id', $approvedItemIds);

            foreach ($approvedItems as $item) {
                $updateData = [];

                if (Schema::hasColumn('purchase_request_items', 'gm_not_approved_reason')) {
                    $updateData['gm_not_approved_reason'] = null;
                }

                if (! empty($updateData)) {
                    $item->update($updateData);
                }
            }

            $reasonItems = $purchaseRequest->items
                ->whereIn('id', $reasonItemIds);

            if ($reasonItems->isNotEmpty()) {
                $childPurchaseRequest = $this->createChildPurchaseRequest(
                    $purchaseRequest,
                    $validated['deferred_until'],
                    null
                );

                foreach ($reasonItems as $item) {
                    $updateData = [
                        'purchase_request_id' => $childPurchaseRequest->id,
                    ];

                    if (Schema::hasColumn('purchase_request_items', 'gm_not_approved_reason')) {
                        $detail = $reasonDetails[(int) $item->id] ?? null;
                        $updateData['gm_not_approved_reason'] = $detail
                            ? 'Reason: ' . $detail
                            : 'Reason';
                    }

                    $item->update($updateData);
                }

                $reasonLogMessage = $reasonItems
                    ->map(function ($item) use ($reasonDetails) {
                        $detail = $reasonDetails[(int) $item->id] ?? '-';

                        return "{$item->item_name}: {$detail}";
                    })
                    ->implode('; ');

                $this->writeLitePrLog(
                    $childPurchaseRequest,
                    'created_from_gm_partial_approval',
                    null,
                    $childPurchaseRequest->status,
                    'Created from ' . $purchaseRequest->request_number . '. Deferred items: ' . $reasonLogMessage . '. Sent back to Purchasing by GM. Before submitting this PR back to Cost Control, please check the vendor prices again.'
                );
            }

            $canceledItems = $purchaseRequest->items
                ->whereIn('id', $canceledItemIds);

            foreach ($canceledItems as $item) {
                $updateData = [];

                if (Schema::hasColumn('purchase_request_items', 'gm_not_approved_reason')) {
                    $updateData['gm_not_approved_reason'] = 'Canceled';
                }

                if (! empty($updateData)) {
                    $item->update($updateData);
                }
            }

            $purchaseRequest->update([
                'status' => 'gm_approved',
                'approved_at' => now(),
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $message = 'GM approved selected items.';

            if ($childPurchaseRequest) {
                $message .= ' Items with Reason were moved to ' . $childPurchaseRequest->request_number . '. The child PR was sent back to Purchasing as Hold by GM.';
            }

            if ($canceledItems->isNotEmpty()) {
                $message .= ' Canceled items were not moved to the child PR: ' . $canceledItems->pluck('item_name')->implode(', ') . '.';
            }

            $this->writeLitePrLog(
                $purchaseRequest,
                'gm_approved_items',
                $fromStatus,
                'gm_approved',
                $message
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'GM approval has been saved successfully.');
    }

    public function sendBackToPurchasing(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isGmUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_gm') {
            return back()->with('error', 'This purchase request is not waiting for GM approval.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $fromStatus = $purchaseRequest->status;

            $purchaseRequest->update([
                'status' => 'revision_to_purchasing_from_gm',
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->writeLitePrLog(
                $purchaseRequest,
                'gm_send_back_to_purchasing',
                $fromStatus,
                'revision_to_purchasing_from_gm',
                $validated['message']
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been sent back to Purchasing.');
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        abort_unless($this->isGmUser(), 403);

        if ($purchaseRequest->status !== 'submitted_to_gm') {
            return back()->with('error', 'This purchase request is not waiting for GM approval.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $validated) {
            $fromStatus = $purchaseRequest->status;

            $updateData = [
                'status' => 'rejected',
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ];

            if (Schema::hasColumn('purchase_requests', 'cancelled_at')) {
                $updateData['cancelled_at'] = now();
            }

            $purchaseRequest->update($updateData);

            $this->writeLitePrLog(
                $purchaseRequest,
                'gm_reject',
                $fromStatus,
                'rejected',
                $validated['message']
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been rejected.');
    }

    private function createChildPurchaseRequest(
        PurchaseRequest $parent,
        string $deferredUntil,
        ?string $gmNote = null
    ): PurchaseRequest {
        $requestNumber = $this->generateChildRequestNumber($parent);

        $requestNotes = trim((string) $parent->request_notes);

        $splitNote = 'Split from ' . $parent->request_number . ' by GM partial approval. Deferred until ' . $deferredUntil . '. Sent back to Purchasing by GM. Before submitting this PR back to Cost Control, please check the vendor prices again.';

        if ($gmNote) {
            $splitNote .= ' GM note: ' . $gmNote;
        }

        $combinedNotes = $requestNotes
            ? $requestNotes . "\n\n" . $splitNote
            : $splitNote;

        $data = [
            'request_number' => $requestNumber,
            'requested_by' => $parent->requested_by,
            'requester_name' => $parent->requester_name,
            'department_name' => $parent->department_name,
            'title' => $parent->title . ' - Deferred Items',
            'priority' => $parent->priority,
            'date_needed' => $deferredUntil,
            'status' => 'on_hold_by_gm',
            'request_notes' => $combinedNotes,
            'current_status_at' => now(),
            'last_activity_at' => now(),
            'vendor_comparison_mode' => $parent->vendor_comparison_mode ?? 'item',
        ];

        if (Schema::hasColumn('purchase_requests', 'parent_purchase_request_id')) {
            $data['parent_purchase_request_id'] = $parent->id;
        }

        if (Schema::hasColumn('purchase_requests', 'deferred_until')) {
            $data['deferred_until'] = $deferredUntil;
        }

        if (Schema::hasColumn('purchase_requests', 'split_reason')) {
            $data['split_reason'] = $splitNote;
        }

        return PurchaseRequest::create($data);
    }

    private function generateChildRequestNumber(PurchaseRequest $parent): string
    {
        $baseNumber = $parent->request_number;

        for ($suffix = 2; $suffix <= 99; $suffix++) {
            $requestNumber = $baseNumber . '-' . $suffix;

            $exists = PurchaseRequest::query()
                ->where('request_number', $requestNumber)
                ->exists();

            if (! $exists) {
                return $requestNumber;
            }
        }

        return $baseNumber . '-' . now()->format('His');
    }

    private function isGmUser(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = strtolower((string) ($user->role ?? ''));
        $role = str_replace(['-', '_'], ' ', $role);

        return in_array($role, [
            'admin',
            'gm',
            'general manager',
        ], true);
    }

    private function writeLitePrLog(
        PurchaseRequest $purchaseRequest,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $message = null
    ): void {
        if (! Schema::hasTable('purchase_request_logs')) {
            return;
        }

        $now = now();
        $user = Auth::user();

        $data = [];

        if (Schema::hasColumn('purchase_request_logs', 'purchase_request_id')) {
            $data['purchase_request_id'] = $purchaseRequest->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'user_id')) {
            $data['user_id'] = $user?->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'role')) {
            $data['role'] = $user?->role;
        }

        if (Schema::hasColumn('purchase_request_logs', 'action')) {
            $data['action'] = $action;
        }

        if (Schema::hasColumn('purchase_request_logs', 'from_status')) {
            $data['from_status'] = $fromStatus;
        }

        if (Schema::hasColumn('purchase_request_logs', 'to_status')) {
            $data['to_status'] = $toStatus;
        }

        if (Schema::hasColumn('purchase_request_logs', 'message')) {
            $data['message'] = $message;
        } elseif (Schema::hasColumn('purchase_request_logs', 'notes')) {
            $data['notes'] = $message;
        } elseif (Schema::hasColumn('purchase_request_logs', 'remarks')) {
            $data['remarks'] = $message;
        }

        if (Schema::hasColumn('purchase_request_logs', 'acted_at')) {
            $data['acted_at'] = $now;
        }

        if (Schema::hasColumn('purchase_request_logs', 'created_at')) {
            $data['created_at'] = $now;
        }

        if (Schema::hasColumn('purchase_request_logs', 'updated_at')) {
            $data['updated_at'] = $now;
        }

        if (! empty($data)) {
            DB::table('purchase_request_logs')->insert($data);
        }
    }
}
