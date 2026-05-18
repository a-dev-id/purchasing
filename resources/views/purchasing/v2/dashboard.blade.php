@extends('layouts.purchasing-lite')

@section('title', 'Dashboard - Nandini Purchasing Lite')

@section('content')
@php
$isFinancialController = $isFinancialController ?? false;
$canUpdateActionStatus = $canUpdateActionStatus ?? $isFinancialController;

$totalShowing = $purchaseRequests->count();

$waitingPurchasing = $purchaseRequests->whereIn('status', [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
'on_hold_by_gm',
])->count();

$waitingAccounting = $purchaseRequests->where('status', 'submitted_to_accounting')->count();

$waitingGm = $purchaseRequests->where('status', 'submitted_to_gm')->count();

$statusLabels = [
'submitted' => 'ON PURCHASING',
'submitted_to_accounting' => 'ACCOUNTING',
'submitted_to_gm' => 'WAITING GM',
'gm_approved' => 'APPROVED BY GM',
'approved' => 'DONE',
'done' => 'DONE',
'on_progress' => 'ON PROGRESS',
'on_shipping' => 'ON SHIPPING',
'purchase' => 'PURCHASE',
'pending' => 'PENDING',
'waiting_for_payment' => 'WAITING FOR PAYMENT',
'on_hold_by_gm' => 'HOLD BY GM',
'revision_to_purchasing_from_gm' => 'RETURNED',
'revision_to_purchasing_from_accounting' => 'RETURNED',
'rejected' => 'REJECTED',
'cancelled' => 'CANCEL',
'canceled' => 'CANCEL',
];

$statusClasses = [
'submitted' => 'bg-blue-100 text-blue-800 border-blue-300',
'submitted_to_accounting' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
'submitted_to_gm' => 'bg-blue-100 text-blue-800 border-blue-300',
'gm_approved' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
'approved' => 'bg-green-100 text-green-800 border-green-300',
'done' => 'bg-green-100 text-green-800 border-green-300',
'on_progress' => 'bg-green-100 text-green-800 border-green-300',
'on_shipping' => 'bg-purple-100 text-purple-800 border-purple-300',
'purchase' => 'bg-blue-100 text-blue-800 border-blue-300',
'pending' => 'bg-purple-100 text-purple-800 border-purple-300',
'waiting_for_payment' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
'on_hold_by_gm' => 'bg-orange-100 text-orange-800 border-orange-300',
'revision_to_purchasing_from_gm' => 'bg-red-100 text-red-800 border-red-300',
'revision_to_purchasing_from_accounting' => 'bg-red-100 text-red-800 border-red-300',
'rejected' => 'bg-red-600 text-white border-red-700',
'cancelled' => 'bg-red-600 text-white border-red-700',
'canceled' => 'bg-red-600 text-white border-red-700',
];

$priorityLabels = [
'urgent' => 'URGENT',
'important' => 'IMPORTANT',
'regular' => 'NORMAL',
];

$priorityClasses = [
'urgent' => 'bg-red-100 text-red-800 border-red-300',
'important' => 'bg-green-100 text-green-800 border-green-300',
'regular' => 'bg-gray-100 text-gray-800 border-gray-300',
];

$fcStatusOptions = [
'done' => 'DONE',
'on_progress' => 'ON PROGRESS',
'on_shipping' => 'ON SHIPPING',
'cancelled' => 'CANCEL',
'pending' => 'PENDING',
'waiting_for_payment' => 'WAITING FOR PAYMENT',
'purchase' => 'PURCHASE',
];
@endphp

<div class="mb-4 flex items-start justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Dashboard
        </h2>

        <p class="text-sm text-gray-600">
            Excel-style purchasing tracker
        </p>
    </div>

    <a href="{{ route('purchasing.v2.requests.create') }}" class="inline-block bg-gray-900 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-gray-700">
        + Create Request
    </a>
</div>

<form method="GET" action="{{ route('purchasing.v2.dashboard') }}" class="bg-white border border-gray-300 p-4 mb-4">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
        <div class="lg:col-span-5">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Search
            </label>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PR number, title, requester, department" class="w-full border border-gray-400 px-3 py-2 text-sm rounded">
        </div>

        <div class="lg:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Status
            </label>

            <select name="status" class="w-full border border-gray-400 px-3 py-2 text-sm rounded bg-white">
                <option value="">All Status</option>

                @foreach (($statuses ?? collect()) as $filterStatus)
                <option value="{{ $filterStatus }}" @selected(request('status')===$filterStatus)>
                    {{ $statusLabels[$filterStatus] ?? \Illuminate\Support\Str::of($filterStatus)->replace('_', ' ')->title() }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-3">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Department
            </label>

            <select name="department" class="w-full border border-gray-400 px-3 py-2 text-sm rounded bg-white">
                <option value="">All Department</option>

                @foreach (($departments ?? collect()) as $department)
                <option value="{{ $department }}" @selected(request('department')===$department)>
                    {{ $department }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-1 flex gap-2">
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-gray-700">
                Filter
            </button>

            <a href="{{ route('purchasing.v2.dashboard') }}" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Reset
            </a>
        </div>
    </div>
</form>

<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">Total Showing</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalShowing }}</div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">Waiting Purchasing</div>
        <div class="text-2xl font-bold text-gray-900">{{ $waitingPurchasing }}</div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">Waiting Accounting</div>
        <div class="text-2xl font-bold text-gray-900">{{ $waitingAccounting }}</div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">Waiting GM</div>
        <div class="text-2xl font-bold text-gray-900">{{ $waitingGm }}</div>
    </div>
</div>

<div class="bg-white border border-gray-500 overflow-auto max-h-[72vh]">
    <table class="w-full min-w-[2450px] border-collapse text-[12px]">
        <thead class="sticky top-0 z-20">
            <tr class="bg-purple-200 text-gray-900 uppercase text-[11px]">
                <th class="border border-gray-700 px-2 py-2 text-center w-[60px]">No</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[150px]">No PR</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[120px]">Date Submit</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[120px]">Date Needed</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[170px]">Department</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[150px]">Requester</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[130px]">To Order</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[360px]">Article & Description</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[300px]">Purpose</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[130px]">Item Status</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[220px]">Vendor</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[180px]">PR Status</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[180px]">Price</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[180px]">Last Update</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[100px]">Action</th>
                <th class="border border-gray-700 px-2 py-2 text-center w-[260px]">Remarks</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($purchaseRequests as $purchaseRequest)
            @php
            $items = $purchaseRequest->items->values();
            $rowspan = max($items->count(), 1);

            $status = $purchaseRequest->status ?? '-';
            $statusLabel = $statusLabels[$status] ?? \Illuminate\Support\Str::of($status)->replace('_', ' ')->upper();
            $statusClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800 border-gray-300';

            $priority = strtolower((string) ($purchaseRequest->priority ?? 'regular'));
            $priorityLabel = $priorityLabels[$priority] ?? strtoupper($purchaseRequest->priority ?? 'NORMAL');
            $priorityClass = $priorityClasses[$priority] ?? 'bg-gray-100 text-gray-800 border-gray-300';

            $prStatusFormId = 'pr-status-form-' . $purchaseRequest->id;
            @endphp

            @if ($items->isEmpty())
            <tr class="hover:bg-yellow-50">
                <td class="border border-gray-700 px-2 py-1 text-center align-middle">
                    {{ $loop->iteration }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle font-semibold whitespace-nowrap">
                    {{ $purchaseRequest->request_number ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->created_at)->format('j-M-y') ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->date_needed)->format('j-M-y') ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    {{ $purchaseRequest->department_name ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    {{ $purchaseRequest->requester_name ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center">
                    -
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle font-semibold">
                    {{ $purchaseRequest->title ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    {{ \Illuminate\Support\Str::limit(strip_tags($purchaseRequest->request_notes ?? '-'), 130) }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <span class="inline-flex items-center justify-center min-w-[90px] border rounded-full px-2 py-0.5 text-[11px] font-bold {{ $priorityClass }}">
                        {{ $priorityLabel }}
                    </span>
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    -
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <div class="mb-1">
                        <span class="inline-flex items-center justify-center min-w-[120px] border rounded-full px-2 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    @if ($canUpdateActionStatus)
                    <form id="{{ $prStatusFormId }}" method="POST" action="{{ route('purchasing.v2.requests.fc-status.update', $purchaseRequest) }}">
                        @csrf
                    </form>

                    <select name="status" form="{{ $prStatusFormId }}" class="w-full border border-gray-400 rounded px-2 py-1 text-[11px] font-semibold bg-white">
                        <option value="" disabled @selected(! array_key_exists($status, $fcStatusOptions))>
                            - Select -
                        </option>

                        @foreach ($fcStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status===$value)>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-right">
                    -
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->updated_at)->format('j-M-y H:i') ?? '-' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <a href="{{ route('purchasing.v2.requests.show', $purchaseRequest) }}" class="inline-block bg-gray-900 text-white px-3 py-1 rounded text-[11px] hover:bg-gray-700">
                        View
                    </a>
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    @if ($canUpdateActionStatus)
                    <textarea name="fc_remarks" form="{{ $prStatusFormId }}" rows="2" class="w-full border border-gray-400 rounded px-2 py-1 text-[11px]" placeholder="Input remarks...">{{ old('fc_remarks', $purchaseRequest->fc_remarks ?? '') }}</textarea>

                    <button type="submit" form="{{ $prStatusFormId }}" onclick="return confirm('Update this PR status and remarks?')" class="mt-1 w-full bg-gray-900 text-white px-3 py-1 rounded text-[11px] hover:bg-gray-700">
                        Update
                    </button>
                    @else
                    {{ $purchaseRequest->fc_remarks ?? '-' }}
                    @endif
                </td>
            </tr>
            @else
            @foreach ($items as $itemIndex => $item)
            @php
            $selectedOffer = $item->vendorOffers->firstWhere('is_selected_by_accounting', true);
            $qty = (float) ($item->qty ?? 0);
            $unitPrice = $selectedOffer ? (float) ($selectedOffer->offer_total ?? 0) : 0;
            $totalPrice = $qty * $unitPrice;
            @endphp

            <tr class="hover:bg-yellow-50">
                @if ($itemIndex === 0)
                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 text-center align-middle">
                    {{ $loop->parent->iteration }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle font-semibold whitespace-nowrap">
                    {{ $purchaseRequest->request_number ?? '-' }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->created_at)->format('j-M-y') ?? '-' }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->date_needed)->format('j-M-y') ?? '-' }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle">
                    {{ $purchaseRequest->department_name ?? '-' }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle">
                    {{ $purchaseRequest->requester_name ?? '-' }}
                </td>
                @endif

                <td class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}
                    {{ $item->unit ?? '' }}
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    <div class="font-semibold text-gray-900">
                        {{ $item->item_name ?? $item->item?->name ?? '-' }}
                    </div>

                    @if ($item->specification)
                    <div class="text-[11px] text-gray-600 mt-0.5">
                        {{ \Illuminate\Support\Str::limit(strip_tags($item->specification), 120) }}
                    </div>
                    @endif
                </td>

                @if ($itemIndex === 0)
                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle">
                    {{ \Illuminate\Support\Str::limit(strip_tags($purchaseRequest->request_notes ?? '-'), 180) }}
                </td>
                @endif

                <td class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <span class="inline-flex items-center justify-center min-w-[90px] border rounded-full px-2 py-0.5 text-[11px] font-bold {{ $priorityClass }}">
                        {{ $priorityLabel }}
                    </span>
                </td>

                <td class="border border-gray-700 px-2 py-1 align-middle">
                    {{ $selectedOffer->vendor_name ?? '-' }}
                </td>

                @if ($itemIndex === 0)
                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <div class="mb-1">
                        <span class="inline-flex items-center justify-center min-w-[120px] border rounded-full px-2 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    @if ($canUpdateActionStatus)
                    <form id="{{ $prStatusFormId }}" method="POST" action="{{ route('purchasing.v2.requests.fc-status.update', $purchaseRequest) }}">
                        @csrf
                    </form>

                    <select name="status" form="{{ $prStatusFormId }}" class="w-full border border-gray-400 rounded px-2 py-1 text-[11px] font-semibold bg-white">
                        <option value="" disabled @selected(! array_key_exists($status, $fcStatusOptions))>
                            - Select -
                        </option>

                        @foreach ($fcStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status===$value)>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                </td>
                @endif

                <td class="border border-gray-700 px-2 py-1 align-middle text-right whitespace-nowrap">
                    @if ($selectedOffer && $totalPrice > 0)
                    <span class="font-semibold">
                        Rp {{ number_format($totalPrice, 0, '.', ',') }}
                    </span>
                    @else
                    -
                    @endif
                </td>

                @if ($itemIndex === 0)
                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle text-center whitespace-nowrap">
                    {{ optional($purchaseRequest->updated_at)->format('j-M-y H:i') ?? '-' }}
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle text-center">
                    <a href="{{ route('purchasing.v2.requests.show', $purchaseRequest) }}" class="inline-block bg-gray-900 text-white px-3 py-1 rounded text-[11px] hover:bg-gray-700">
                        View
                    </a>
                </td>

                <td rowspan="{{ $rowspan }}" class="border border-gray-700 px-2 py-1 align-middle">
                    @if ($canUpdateActionStatus)
                    <textarea name="fc_remarks" form="{{ $prStatusFormId }}" rows="2" class="w-full border border-gray-400 rounded px-2 py-1 text-[11px]" placeholder="Input remarks...">{{ old('fc_remarks', $purchaseRequest->fc_remarks ?? '') }}</textarea>

                    <button type="submit" form="{{ $prStatusFormId }}" onclick="return confirm('Update this PR status and remarks?')" class="mt-1 w-full bg-gray-900 text-white px-3 py-1 rounded text-[11px] hover:bg-gray-700">
                        Update
                    </button>
                    @else
                    {{ $purchaseRequest->fc_remarks ?? '-' }}
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
            @endif
            @empty
            <tr>
                <td colspan="16" class="border border-gray-700 px-3 py-8 text-center text-gray-500">
                    No purchase requests found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection