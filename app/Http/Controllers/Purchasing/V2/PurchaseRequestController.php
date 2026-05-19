<?php

namespace App\Http\Controllers\Purchasing\V2;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = PurchaseRequest::query()
            ->withCount('items')
            ->latest('updated_at');

        if ($this->hasUserRole($user, ['requester'])) {
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
        $normalizedRole = $this->resolveUserRole($user);

        $requesterRevisionStatuses = [
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];

        $statuses = match ($normalizedRole) {
            'purchasing',
            'purchase',
            'purchasing staff' => [
                'submitted',
                'on_hold_by_gm',
                'revision_to_purchasing_from_accounting',
                'revision_to_purchasing_from_gm',
            ],

            'accounting',
            'accountant',
            'cost control',
            'cost controller',
            'costcontrol' => [
                'submitted_to_accounting',
                'revision_to_accounting_from_gm',
            ],

            'gm',
            'general manager' => [
                'submitted_to_gm',
            ],

            'financial controller',
            'finance controller',
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

        if ($this->hasUserRole($user, ['requester'])) {
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

        return view('purchasing.v2.requests.index', [
            'user' => $user,
            'purchaseRequests' => $purchaseRequests,
            'statuses' => collect($statuses),
            'departments' => collect(),
        ]);
    }

    public function create(): View
    {
        $user = Auth::user();

        $items = Item::query()
            ->with('photos')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchasing.v2.requests.create', [
            'user' => $user,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => ['nullable', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:50'],
            'date_needed' => ['nullable', 'date'],
            'request_notes' => ['nullable', 'string'],
            'submit_action' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_unit_price' => ['nullable'],
            'items.*.specification' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $itemRows = $this->prepareItemRows($validated['items'] ?? []);

        if ($itemRows->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Please add at least one item.']);
        }

        $submitAction = $validated['submit_action'] ?? 'draft';
        $status = $submitAction === 'submit' ? 'submitted' : 'draft';

        $purchaseRequest = DB::transaction(function () use ($validated, $user, $itemRows, $status) {
            $purchaseRequest = PurchaseRequest::create([
                'request_number' => $this->generateRequestNumber(),
                'requested_by' => $user?->id,
                'requester_name' => $validated['requester_name'] ?? $user?->name,
                'department_name' => $validated['department_name'] ?? $user?->department_name,
                'title' => $validated['title'],
                'priority' => $validated['priority'] ?? 'normal',
                'date_needed' => $validated['date_needed'] ?? null,
                'status' => $status,
                'request_notes' => $validated['request_notes'] ?? null,
                'submitted_at' => $status === 'submitted' ? now() : null,
                'current_status_at' => now(),
                'last_activity_at' => now(),
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
                    : 'Purchase request draft has been saved successfully.'
            );
    }

    public function edit(PurchaseRequest $purchaseRequest): View
    {
        $user = Auth::user();

        $this->authorizeEdit($purchaseRequest, $user);

        $items = Item::query()
            ->with('photos')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchaseRequest->load([
            'items.item.photos',
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

        $this->authorizeEdit($purchaseRequest, $user);

        $validated = $request->validate([
            'requester_name' => ['nullable', 'string', 'max:255'],
            'department_name' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:50'],
            'date_needed' => ['nullable', 'date'],
            'request_notes' => ['nullable', 'string'],
            'submit_action' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_unit_price' => ['nullable'],
            'items.*.specification' => ['nullable', 'string'],
        ]);

        $itemRows = $this->prepareItemRows($validated['items'] ?? []);

        if ($itemRows->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Please add at least one item.']);
        }

        $submitAction = $validated['submit_action'] ?? 'draft';

        DB::transaction(function () use ($purchaseRequest, $validated, $itemRows, $submitAction) {
            $updateData = [
                'requester_name' => $validated['requester_name'] ?? $purchaseRequest->requester_name,
                'department_name' => $validated['department_name'] ?? $purchaseRequest->department_name,
                'title' => $validated['title'],
                'priority' => $validated['priority'] ?? 'normal',
                'date_needed' => $validated['date_needed'] ?? null,
                'request_notes' => $validated['request_notes'] ?? null,
                'last_activity_at' => now(),
            ];

            if ($submitAction === 'submit' && $purchaseRequest->status === 'draft') {
                $updateData['status'] = 'submitted';
                $updateData['submitted_at'] = now();
                $updateData['current_status_at'] = now();
            }

            $purchaseRequest->update($updateData);

            $purchaseRequest->items()->delete();

            $this->syncPurchaseRequestItems($purchaseRequest, $itemRows);
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been updated successfully.');
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        if ($this->hasUserRole($user, ['requester'])) {
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

    public function resubmit(PurchaseRequest $purchaseRequest)
    {
        $user = Auth::user();

        $isAdmin = $this->hasUserRole($user, ['admin', 'super admin']);
        $isRequester = $this->hasUserRole($user, ['requester']);

        $isOwnerOrSameDepartment =
            (int) $purchaseRequest->requested_by === (int) $user?->id
            || (
                ! empty($user?->department_name)
                && $purchaseRequest->department_name === $user->department_name
            );

        if (! $isAdmin && (! $isRequester || ! $isOwnerOrSameDepartment)) {
            abort(403);
        }

        $targetStatus = match ($purchaseRequest->status) {
            'revision_from_purchasing',
            'revision_to_requester_from_purchasing' => 'submitted',

            'revision_from_accounting',
            'revision_to_requester_from_accounting' => 'submitted_to_accounting',

            'revision_from_gm',
            'revision_to_requester_from_gm' => 'submitted_to_gm',

            default => null,
        };

        if (! $targetStatus) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->with('error', 'This purchase request cannot be resubmitted from the current status.');
        }

        if ($purchaseRequest->items()->count() === 0) {
            return redirect()
                ->route('purchasing.v2.requests.show', $purchaseRequest)
                ->withErrors(['items' => 'Please add at least one item before resubmitting.']);
        }

        $fromStatus = $purchaseRequest->status;

        DB::transaction(function () use ($purchaseRequest, $user, $fromStatus, $targetStatus) {
            $updateData = [
                'status' => $targetStatus,
                'current_status_at' => now(),
                'last_activity_at' => now(),
            ];

            if ($targetStatus === 'submitted' && empty($purchaseRequest->submitted_at)) {
                $updateData['submitted_at'] = now();
            }

            $purchaseRequest->update($updateData);

            $this->writeResubmitLog(
                $purchaseRequest,
                $user,
                $fromStatus,
                $targetStatus
            );
        });

        return redirect()
            ->route('purchasing.v2.requests.show', $purchaseRequest)
            ->with('success', 'Purchase request has been resubmitted successfully.');
    }

    private function writeResubmitLog(
        PurchaseRequest $purchaseRequest,
        $user,
        ?string $fromStatus,
        ?string $toStatus
    ): void {
        if (! Schema::hasTable('purchase_request_logs')) {
            return;
        }

        $now = now();

        $data = [];

        if (Schema::hasColumn('purchase_request_logs', 'purchase_request_id')) {
            $data['purchase_request_id'] = $purchaseRequest->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'user_id')) {
            $data['user_id'] = $user?->id;
        }

        if (Schema::hasColumn('purchase_request_logs', 'user_name')) {
            $data['user_name'] = $user?->name;
        }

        if (Schema::hasColumn('purchase_request_logs', 'role')) {
            $data['role'] = $this->getUserRoleForLog($user);
        }

        if (Schema::hasColumn('purchase_request_logs', 'action')) {
            $data['action'] = 'resubmitted_by_requester';
        }

        if (Schema::hasColumn('purchase_request_logs', 'from_status')) {
            $data['from_status'] = $fromStatus;
        }

        if (Schema::hasColumn('purchase_request_logs', 'to_status')) {
            $data['to_status'] = $toStatus;
        }

        foreach (['remark', 'remarks', 'message', 'notes'] as $column) {
            if (Schema::hasColumn('purchase_request_logs', $column)) {
                $data[$column] = 'Requester resubmitted the purchase request.';
            }
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

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $user = Auth::user();

        $this->authorizeView($purchaseRequest, $user);

        $purchaseRequest->load([
            'items.item.photos',
            'items.vendorOffers.vendor',
            'logs.user',
        ]);

        return view('purchasing.v2.requests.show', [
            'user' => $user,
            'purchaseRequest' => $purchaseRequest,
        ]);
    }

    public function searchItems(Request $request)
    {
        $search = $request->string('q')->toString();

        $items = Item::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        return response()->json($items);
    }

    private function authorizeEdit(PurchaseRequest $purchaseRequest, $user): void
    {
        if ($this->hasUserRole($user, ['admin', 'super admin'])) {
            return;
        }

        $editableStatuses = [
            'draft',
            'revision_from_purchasing',
            'revision_from_accounting',
            'revision_from_gm',
            'revision_to_requester_from_purchasing',
            'revision_to_requester_from_accounting',
            'revision_to_requester_from_gm',
        ];

        abort_unless(in_array($purchaseRequest->status, $editableStatuses, true), 403);

        $canAccess = (int) $purchaseRequest->requested_by === (int) $user?->id
            || (
                ! empty($user?->department_name)
                && $purchaseRequest->department_name === $user->department_name
            );

        abort_unless($canAccess, 403);
    }

    private function authorizeView(PurchaseRequest $purchaseRequest, $user): void
    {
        if (! $user) {
            abort(403);
        }

        if ($this->hasUserRole($user, ['admin', 'super admin'])) {
            return;
        }

        if ($this->hasUserRole($user, ['requester'])) {
            $canAccess = (int) $purchaseRequest->requested_by === (int) $user->id
                || (
                    ! empty($user->department_name)
                    && $purchaseRequest->department_name === $user->department_name
                );

            abort_unless($canAccess, 403);

            return;
        }

        if ($this->hasUserRole($user, [
            'purchasing',
            'purchase',
            'purchasing staff',
            'accounting',
            'accountant',
            'cost control',
            'cost controller',
            'costcontrol',
            'gm',
            'general manager',
            'financial controller',
            'finance controller',
            'fc',
        ])) {
            return;
        }

        $canAccessByOwnerOrDepartment = (int) $purchaseRequest->requested_by === (int) $user->id
            || (
                ! empty($user->department_name)
                && $purchaseRequest->department_name === $user->department_name
            );

        abort_unless($canAccessByOwnerOrDepartment, 403);
    }

    private function hasUserRole($user, array $roles): bool
    {
        if (! $user) {
            return false;
        }

        $normalizedRoles = array_map(
            fn($role) => $this->normalizeRole($role),
            $roles
        );

        foreach ($this->getUserRoleValues($user) as $roleValue) {
            if (in_array($this->normalizeRole($roleValue), $normalizedRoles, true)) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role) || $user->hasRole($this->normalizeRole($role))) {
                    return true;
                }
            }
        }

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($roles) || $user->hasAnyRole($normalizedRoles)) {
                return true;
            }
        }

        return false;
    }

    private function resolveUserRole($user): string
    {
        $priorityRoles = [
            'admin',
            'super admin',
            'purchasing',
            'purchase',
            'purchasing staff',
            'accounting',
            'accountant',
            'cost control',
            'cost controller',
            'costcontrol',
            'gm',
            'general manager',
            'financial controller',
            'finance controller',
            'fc',
            'requester',
        ];

        foreach ($priorityRoles as $role) {
            if ($this->hasUserRole($user, [$role])) {
                return $this->normalizeRole($role);
            }
        }

        foreach ($this->getUserRoleValues($user) as $roleValue) {
            $normalizedRole = $this->normalizeRole($roleValue);

            if ($normalizedRole !== '') {
                return $normalizedRole;
            }
        }

        return '';
    }

    private function getUserRoleValues($user): array
    {
        if (! $user) {
            return [];
        }

        $roles = [];

        foreach (
            [
                'role',
                'role_name',
                'user_role',
                'account_type',
                'type',
                'position',
                'department',
                'department_name',
            ] as $field
        ) {
            if (! empty($user->{$field})) {
                $roles[] = (string) $user->{$field};
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            foreach ($user->getRoleNames() as $roleName) {
                $roles[] = (string) $roleName;
            }
        }

        if (isset($user->roles)) {
            foreach ($user->roles as $role) {
                if (is_string($role)) {
                    $roles[] = $role;
                    continue;
                }

                foreach (['name', 'role', 'role_name', 'title'] as $field) {
                    if (! empty($role->{$field})) {
                        $roles[] = (string) $role->{$field};
                    }
                }
            }
        }

        return array_values(array_filter(array_unique($roles)));
    }

    private function normalizeRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));
        $role = str_replace(['-', '_'], ' ', $role);
        $role = preg_replace('/\s+/', ' ', $role);

        return trim($role);
    }

    private function getUserRoleForLog($user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (
            [
                'role',
                'role_name',
                'user_role',
                'account_type',
                'type',
                'position',
                'department_name',
            ] as $field
        ) {
            if (! empty($user->{$field})) {
                return (string) $user->{$field};
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->first();
        }

        return null;
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
