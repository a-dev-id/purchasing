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
            'items.*.specification' => ['nullable', 'string'],
        ]);

        $itemRows = collect($validated['items'] ?? [])
            ->filter(function ($row) {
                return ! empty($row['item_id']) || ! empty($row['item_name']);
            })
            ->values();

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

            foreach ($itemRows as $index => $row) {
                $masterItem = null;

                if (! empty($row['item_id'])) {
                    $masterItem = Item::query()->find($row['item_id']);
                }

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_id' => $masterItem?->id,
                    'item_name' => $row['item_name'] ?: $masterItem?->name,
                    'specification' => $row['specification'] ?? $masterItem?->default_specification,
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?? ($masterItem?->default_unit ?? 'pcs'),
                    'sort_order' => $index + 1,
                ]);
            }

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

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        if (($user->role ?? null) === 'requester') {
            abort_unless((int) $purchaseRequest->requested_by === (int) $user->id, 403);
        }

        if ($purchaseRequest->status !== 'draft') {
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
