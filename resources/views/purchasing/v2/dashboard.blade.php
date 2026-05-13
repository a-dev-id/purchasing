@extends('layouts.purchasing-lite')

@section('title', 'Dashboard - Nandini Purchasing Lite')

@section('content')
<div class="mb-4">
    <h2 class="text-lg font-bold text-gray-900">
        Dashboard
    </h2>

    <p class="text-sm text-gray-600">
        Latest purchase requests
    </p>
</div>

{{-- Summary Boxes --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">
            Total Showing
        </div>

        <div class="text-2xl font-bold text-gray-900">
            {{ $purchaseRequests->count() }}
        </div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">
            Waiting Purchasing
        </div>

        <div class="text-2xl font-bold text-gray-900">
            {{ $purchaseRequests->where('status', 'submitted')->count() }}
        </div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">
            Waiting Accounting
        </div>

        <div class="text-2xl font-bold text-gray-900">
            {{ $purchaseRequests->where('status', 'submitted_to_accounting')->count() }}
        </div>
    </div>

    <div class="bg-white border border-gray-300 p-4">
        <div class="text-xs text-gray-500 mb-1">
            Waiting GM
        </div>

        <div class="text-2xl font-bold text-gray-900">
            {{ $purchaseRequests->where('status', 'submitted_to_gm')->count() }}
        </div>
    </div>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-400 overflow-x-auto">
    <table class="w-full min-w-[1000px] border-collapse text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">PR No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Date</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Department</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Requester</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Request Name</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Total Items</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Status</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Last Update</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($purchaseRequests as $index => $purchaseRequest)
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $index + 1 }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap font-semibold">
                    {{ $purchaseRequest->request_number ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ optional($purchaseRequest->created_at)->format('d/m/Y') }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $purchaseRequest->department_name ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $purchaseRequest->requester_name ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $purchaseRequest->title ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $purchaseRequest->items_count ?? 0 }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    <span class="inline-block border border-gray-400 bg-gray-50 px-2 py-1 text-xs font-bold rounded">
                        {{ \Illuminate\Support\Str::of($purchaseRequest->status ?? '-')->replace('_', ' ')->title() }}
                    </span>
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ optional($purchaseRequest->updated_at)->format('d/m/Y H:i') }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    <a href="{{ route('purchasing.v2.requests.show', $purchaseRequest) }}" class="inline-block bg-gray-900 text-white px-3 py-1 rounded text-xs hover:bg-gray-700">
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                    No purchase requests found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection