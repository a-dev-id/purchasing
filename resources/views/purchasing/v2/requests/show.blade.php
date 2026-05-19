@extends('layouts.purchasing-lite')

@section('title', ($purchaseRequest->request_number ?? 'Purchase Request') . ' - Nandini Purchasing Lite')

@section('content')
@php
$user = auth()->user();

$userRole = strtolower((string) ($user->role ?? $user->role_name ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$isPurchasingAccount =
in_array($normalizedRole, [
'purchasing',
'purchase',
'purchasing staff',
], true)
|| (
$user
&& method_exists($user, 'hasRole')
&& (
$user->hasRole('purchasing')
|| $user->hasRole('purchase')
|| $user->hasRole('purchasing staff')
|| $user->hasRole('purchasing_staff')
)
);

$latestActionRemark = null;
$latestActionLabel = null;

$returnedStatuses = [
'revision_from_purchasing',
'revision_from_accounting',
'revision_from_gm',
'revision_to_requester_from_purchasing',
'revision_to_requester_from_accounting',
'revision_to_requester_from_gm',
];

$rejectedStatuses = [
'rejected',
];

$showActionNotes = in_array($purchaseRequest->status, $returnedStatuses, true)
|| in_array($purchaseRequest->status, $rejectedStatuses, true);

if (
$showActionNotes
&& \Illuminate\Support\Facades\Schema::hasTable('purchase_request_logs')
&& \Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', 'purchase_request_id')
) {
$messageColumn = null;

foreach (['message', 'remarks', 'notes'] as $column) {
if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', $column)) {
$messageColumn = $column;
break;
}
}

if ($messageColumn) {
$orderColumn = 'id';

if (\Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', 'acted_at')) {
$orderColumn = 'acted_at';
} elseif (\Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', 'created_at')) {
$orderColumn = 'created_at';
}

$latestLog = \Illuminate\Support\Facades\DB::table('purchase_request_logs')
->where('purchase_request_id', $purchaseRequest->id)
->whereNotNull($messageColumn)
->where($messageColumn, '!=', '')
->when(\Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', 'action'), function ($query) {
$query->whereIn('action', [
'purchasing_return_to_requester',
'gm_send_back_to_requester',
'gm_send_back_to_purchasing',
'purchasing_reject',
'gm_reject',
]);
})
->orderByDesc($orderColumn)
->orderByDesc('id')
->first();

if ($latestLog) {
$latestActionRemark = trim((string) ($latestLog->{$messageColumn} ?? ''));

if (
\Illuminate\Support\Facades\Schema::hasColumn('purchase_request_logs', 'action')
&& ! empty($latestLog->action)
) {
$latestActionLabel = ucwords(str_replace('_', ' ', (string) $latestLog->action));
}
}
}
}

$isRejected = in_array($purchaseRequest->status, $rejectedStatuses, true);
$showActionNotes = $showActionNotes && filled($latestActionRemark);
@endphp

@include('purchasing.v2.requests.partials.flash')

@include('purchasing.v2.requests.partials.header')

@include('purchasing.v2.requests.partials.summary-card')

@include('purchasing.v2.requests.partials.requested-items-table', [
'isPurchasingAccount' => $isPurchasingAccount,
])

@if ($showActionNotes)
<div class="mt-6 bg-white border border-gray-300 p-4">
    <h3 class="text-base font-bold text-gray-900 mb-4">
        {{ $isRejected ? 'Rejection Notes' : 'Request Update / Return Notes' }}
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">
                Latest Action
            </div>

            <div class="font-semibold {{ $isRejected ? 'text-red-700' : 'text-gray-900' }}">
                {{ $latestActionLabel ?: '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">
                {{ $isRejected ? 'Rejection Message' : 'Latest Message' }}
            </div>

            <div class="text-gray-900">
                {{ trim($latestActionRemark) }}
            </div>
        </div>
    </div>
</div>
@endif

@include('purchasing.v2.requests.partials.vendor-search-script')

@include('purchasing.v2.requests.partials.actions')
@endsection