@extends('layouts.purchasing-lite')

@section('title', 'Need My Action - Nandini Purchasing Lite')

@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Need My Action
        </h2>

        <p class="text-sm text-gray-600">
            Purchase requests waiting for your review
        </p>
    </div>
</div>

<form method="GET" action="{{ route('purchasing.v2.need-my-action') }}" class="bg-white border border-gray-300 p-4 mb-4">

    <div class="grid grid-cols-1 md:grid-cols-[2fr_auto_auto] gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Search
            </label>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PR number, title, requester, department" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
        </div>

        <div>
            <button type="submit" class="w-full md:w-auto bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Filter
            </button>
        </div>

        <div>
            <a href="{{ route('purchasing.v2.need-my-action') }}" class="block text-center bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Reset
            </a>
        </div>
    </div>
</form>

<div class="bg-white border border-gray-400 overflow-x-auto">
    <table class="w-full min-w-[1100px] border-collapse text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">PR No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Date</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Department</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Requester</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Request Name</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Items</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Status</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($purchaseRequests as $purchaseRequest)
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ ($purchaseRequests->currentPage() - 1) * $purchaseRequests->perPage() + $loop->iteration }}
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

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right">
                    {{ $purchaseRequest->items_count ?? 0 }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    <span class="inline-block border border-blue-600 bg-blue-50 text-blue-700 px-2 py-1 text-xs font-bold rounded">
                        {{ \Illuminate\Support\Str::of($purchaseRequest->status ?? '-')->replace('_', ' ')->title() }}
                    </span>
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    <a href="{{ route('purchasing.v2.requests.show', $purchaseRequest) }}" class="inline-block bg-gray-900 text-white px-3 py-1 rounded text-xs hover:bg-gray-700">
                        Review
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                    No purchase requests need your action.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $purchaseRequests->links() }}
</div>
@endsection