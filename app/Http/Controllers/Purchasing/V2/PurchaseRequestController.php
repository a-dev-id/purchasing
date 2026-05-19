<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = PurchaseRequest::query()
            ->withCount('items')
            ->latest('updated_at');

        if (($user->role ?? null) === 'requester') {
            $query->where(function ($query) use ($user) {
                $query->where('requested_by', $user->id);

                if (! empty($user->department_name)) {
                    $query->orWhere('department_name', $user->department_name);
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('department')) {
            $query->where('department_name', $request->string('department')->toString());
        }

        $purchaseRequests = $query
            ->paginate(25)
            ->withQueryString();

        $statuses = PurchaseRequest::query()
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $departments = PurchaseRequest::query()
            ->whereNotNull('department_name')
            ->select('department_name')
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name');

        return view('purchasing.v2.requests.index', [
            'user' => $user,
            'purchaseRequests' => $purchaseRequests,
            'statuses' => $statuses,
            'departments' => $departments,
        ]);
    }

    public function needMyAction(Request $request): View
    {
        $user = Auth::user();

        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        $requesterRevisionStatuses = [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];

        $statuses = match ($normalizedRole) {
            'purchasing' => [
                'submitted',
                'on_hold_by_gm',
                'revision_to_purchasing_from_accounting',
                'revision_to_purchasing_from_gm',
            ],

            'accounting',
            'cost control',
            'cost controller' => [
                'submitted_to_accounting',
                'revision_to_accounting_from_gm',
            ],

            'gm',
            'general manager' => [
                'submitted_to_gm',
            ],

            'financial controller',
            'fc' => [
                'gm_approved',
                'waiting_payment_by_fc',
                'paid_to_vendor_by_fc',
                'item_arrived_by_fc',
                'done',
                'pending',
                'purchase',
                'on_progress',
                'on_shipping',
            ],

            default => $requesterRevisionStatuses,
        };

        $query = PurchaseRequest::query()
            ->withCount('items')
            ->whereIn('status', $statuses)
            ->latest('updated_at');

        if (! in_array($normalizedRole, [
            'admin',
            'purchasing',
            'accounting',
            'cost control',
            'cost controller',
            'gm',
            'general manager',
            'financial controller',
            'fc',
        ], true)) {
            $query->where(function ($query) use ($user) {
                $query->where('requested_by', $user->id);

                if (! empty($user->department_name)) {
                    $query->orWhere('department_name', $user->department_name);
                }
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        $purchaseRequests = $query
            ->paginate(25)
            ->withQueryString();

        return view('purchasing.v2.requests.need-my-action', [
            'user' => $user,
            'purchaseRequests' => $purchaseRequests,
            'statuses' => $statuses,
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();

        $items = Item::query()
            ->with('photos')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'sku',
                'category',
                'brand',
                'default_unit',
                'default_specification',
                'last_price',
                'currency',
            ]);

        return view('purchasing.v2.requests.create', [
            'user' => $user,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'requester_name' => ['required', 'string', 'max:191'],
            'department_name' => ['nullable', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:191'],
            'priority' => ['required', 'string', 'in:normal,high,urgent'],
            'date_needed' => ['nullable', 'date'],
            'request_notes' => ['nullable', 'string'],

            'submit_action' => ['nullable', 'string', 'in:draft,submit'],

            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:191'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_unit_price' => ['nullable'],
            'items.*.specification' => ['nullable', 'string'],
        ]);

        $itemRows = $this->prepareItemRows($validated['items'] ?? []);

        if ($itemRows->isEmpty()) {
            return back()
                ->withErrors(['items' => 'Please choose at least one item.'])
                ->withInput();
        }

        foreach ($itemRows as $row) {
            if ((float) ($row['qty'] ?? 0) <= 0) {
                return back()
                    ->withErrors(['items' => 'Please enter quantity for all selected items.'])
                    ->withInput();
            }
        }

        $status = ($validated['submit_action'] ?? 'draft') === 'submit'
            ? 'submitted'
            : 'draft';

        $purchaseRequest = DB::transaction(function () use ($validated, $itemRows, $user, $status) {
            $purchaseRequest = PurchaseRequest::create([
                'request_number' => $this->generateRequestNumber(),
                'requested_by' => $user->id,
                'requester_name' => $validated['requester_name'],
                'department_name' => $validated['department_name'] ?? ($user->department_name ?? null),
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'date_needed' => $validated['date_needed'] ?? null,
                'status' => $status,
                'request_notes' => $validated['request_notes'] ?? null,
                'current_status_at' => now(),
                'last_activity_at' => now(),
                'submitted_at' => $status === 'submitted' ? now() : null,
                'vendor_comparison_mode' => 'item',
            ]);

            $this->syncPurchaseRequestItems($purchaseRequest, $itemRows);

            return $purchaseRequest;
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with(
                'success',
                $status === 'submitted'
                    ? 'Purchase request has been submitted successfully.'
                    : 'Purchase request has been saved as draft.'
            );
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $this->authorizeDraftAccess($purchaseRequest, $user);

        if (! in_array($purchaseRequest->status, $this->editableStatuses(), true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors(['status' => 'Only draft or returned purchase requests can be edited.']);
        }

        $purchaseRequest->load([
            'items.item.photos',
        ]);

        $items = Item::query()
            ->with('photos')
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'sku',
                'category',
                'brand',
                'default_unit',
                'default_specification',
                'last_price',
                'currency',
            ]);

        return view('purchasing.v2.requests.edit', [
            'user' => $user,
            'purchaseRequest' => $purchaseRequest,
            'items' => $items,
        ]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $this->authorizeDraftAccess($purchaseRequest, $user);

        if (! in_array($purchaseRequest->status, $this->editableStatuses(), true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors(['status' => 'Only draft or returned purchase requests can be edited.']);
        }

        $validated = $request->validate([
            'requester_name' => ['required', 'string', 'max:191'],
            'department_name' => ['nullable', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:191'],
            'priority' => ['required', 'string', 'in:normal,high,urgent'],
            'date_needed' => ['nullable', 'date'],
            'request_notes' => ['nullable', 'string'],

            'submit_action' => ['nullable', 'string', 'in:draft,submit'],

            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:191'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_unit_price' => ['nullable'],
            'items.*.specification' => ['nullable', 'string'],
        ]);

        $itemRows = $this->prepareItemRows($validated['items'] ?? []);

        if ($itemRows->isEmpty()) {
            return back()
                ->withErrors(['items' => 'Please choose at least one item.'])
                ->withInput();
        }

        foreach ($itemRows as $row) {
            if ((float) ($row['qty'] ?? 0) <= 0) {
                return back()
                    ->withErrors(['items' => 'Please enter quantity for all selected items.'])
                    ->withInput();
            }
        }

        $previousStatus = $purchaseRequest->status;
        $submitAction = $validated['submit_action'] ?? 'draft';

        $status = $this->resolveUpdatedStatus($previousStatus, $submitAction);

        DB::transaction(function () use ($validated, $itemRows, $purchaseRequest, $user, $status, $previousStatus) {
            $purchaseRequest->update([
                'requester_name' => $validated['requester_name'],
                'department_name' => $validated['department_name'] ?? ($user->department_name ?? null),
                'title' => $validated['title'],
                'priority' => $validated['priority'],
                'date_needed' => $validated['date_needed'] ?? null,
                'status' => $status,
                'request_notes' => $validated['request_notes'] ?? null,
                'current_status_at' => now(),
                'last_activity_at' => now(),
                'submitted_at' => $status === 'submitted' ? now() : $purchaseRequest->submitted_at,
            ]);

            $purchaseRequest->items()->delete();

            $this->syncPurchaseRequestItems($purchaseRequest, $itemRows);
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with(
                'success',
                $status === 'submitted'
                    ? 'Purchase request has been updated and submitted back to Purchasing successfully.'
                    : 'Purchase request has been updated successfully.'
            );
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        if (($user->role ?? null) === 'requester') {
            abort_unless((int) $purchaseRequest->requested_by === (int) $user->id, 403);
        }

        if (! in_array($purchaseRequest->status, [
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ], true)) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->with('success', 'This purchase request has already been submitted.');
        }

        if ($purchaseRequest->items()->count() === 0) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors(['items' => 'Please add at least one item before submitting.']);
        }

        $purchaseRequest->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'current_status_at' => now(),
            'last_activity_at' => now(),
        ]);

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been submitted successfully.');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $user = Auth::user();

        if (($user->role ?? null) === 'requester') {
            $canView = $purchaseRequest->requested_by === $user->id
                || $purchaseRequest->department_name === $user->department_name;

            abort_unless($canView, 403);
        }

        $purchaseRequest->load([
            'items.item.photos',
            'items.vendorOffers' => function ($query) {
                $query->orderBy('offer_rank');
            },
        ]);

        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'category',
                'contact_person',
                'phone',
                'email',
            ]);

        return view('purchasing.v2.requests.show', [
            'user' => $user,
            'purchaseRequest' => $purchaseRequest,
            'vendors' => $vendors,
        ]);
    }

    public function searchItems(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $items = Item::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhere('brand', 'like', '%' . $search . '%')
                        ->orWhere('default_specification', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get([
                'id',
                'name',
                'sku',
                'category',
                'brand',
                'default_unit',
                'default_specification',
                'last_price',
                'currency',
            ])
            ->map(function ($item) {
                $lastPrice = (float) ($item->last_price ?? 0);

                return [
                    'id' => $item->id,
                    'text' => $item->name,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'category' => $item->category,
                    'brand' => $item->brand,
                    'default_unit' => $item->default_unit,
                    'unit' => $item->default_unit,
                    'default_specification' => $item->default_specification,
                    'specification' => $item->default_specification,
                    'last_price' => $lastPrice,
                    'estimated_unit_price' => $lastPrice,
                    'currency' => $item->currency ?: 'IDR',
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
            'results' => $items,
        ]);
    }

    private function editableStatuses(): array
    {
        return [
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];
    }

    private function returnedStatuses(): array
    {
        return [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];
    }

    private function resolveUpdatedStatus(string $previousStatus, string $submitAction): string
    {
        if ($submitAction === 'submit') {
            return 'submitted';
        }

        if (in_array($previousStatus, $this->returnedStatuses(), true)) {
            return $previousStatus;
        }

        return 'draft';
    }

    private function authorizeDraftAccess(PurchaseRequest $purchaseRequest, $user): void
    {
        $role = strtolower((string) ($user->role ?? ''));
        $normalizedRole = str_replace(['-', '_'], ' ', $role);

        if (in_array($normalizedRole, [
            'admin',
            'requester',
        ], true)) {
            if ($normalizedRole === 'requester') {
                $canAccess = (int) $purchaseRequest->requested_by === (int) $user->id
                    || (
                        ! empty($user->department_name)
                        && $purchaseRequest->department_name === $user->department_name
                    );

                abort_unless($canAccess, 403);
            }

            return;
        }

        $canAccessByDepartment = ! empty($user->department_name)
            && $purchaseRequest->department_name === $user->department_name;

        abort_unless($canAccessByDepartment, 403);
    }

    private function prepareItemRows(array $items)
    {
        return collect($items)
            ->filter(function ($row) {
                return ! empty($row['item_id']) || ! empty($row['item_name']);
            })
            ->values();
    }

    private function syncPurchaseRequestItems(PurchaseRequest $purchaseRequest, $itemRows): void
    {
        foreach ($itemRows as $index => $row) {
            $masterItem = null;

            if (! empty($row['item_id'])) {
                $masterItem = Item::query()->find($row['item_id']);
            }

            $qty = (float) ($row['qty'] ?? 0);

            $estimatedUnitPrice = $this->normalizeMoney(
                $row['estimated_unit_price'] ?? $masterItem?->last_price ?? 0
            );

            PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_id' => $masterItem?->id,
                'item_name' => ($row['item_name'] ?? null) ?: $masterItem?->name,
                'specification' => $row['specification'] ?? $masterItem?->default_specification,
                'qty' => $qty,
                'unit' => $row['unit'] ?? ($masterItem?->default_unit ?? 'pcs'),
                'estimated_unit_price' => $estimatedUnitPrice,
                'estimated_total_price' => $estimatedUnitPrice * $qty,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function normalizeMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9,\.]/', '', (string) $value);

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $lastComma = strrpos($clean, ',');
            $lastDot = strrpos($clean, '.');

            if ($lastComma > $lastDot) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }

            return (float) $clean;
        }

        if (str_contains($clean, ',')) {
            $parts = explode(',', $clean);

            if (count($parts) > 2) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }

            return (float) $clean;
        }

        if (substr_count($clean, '.') > 1) {
            $clean = str_replace('.', '', $clean);
        }

        return (float) ($clean ?: 0);
    }

    private function generateRequestNumber(): string
    {
        $prefix = 'PR' . now()->format('Ymd');

        $nextNumber = PurchaseRequest::query()
            ->where('request_number', 'like', $prefix . '-%')
            ->count() + 1;

        do {
            $requestNumber = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $exists = PurchaseRequest::query()
                ->where('request_number', $requestNumber)
                ->exists();

            $nextNumber++;
        } while ($exists);

        return $requestNumber;
    }
}
