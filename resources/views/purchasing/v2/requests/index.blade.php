@extends('layouts.purchasing-lite')

@section('title', 'All Requests - Nandini Purchasing Lite')

@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Purchase Requests
        </h2>

        <p class="text-sm text-gray-600">
            All purchase requests in simple table view
        </p>
    </div>

    <div>
        <a href="{{ route('purchasing.v2.requests.create') }}" class="inline-block bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            + Create Request
        </a>
    </div>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('purchasing.v2.requests.index') }}" class="bg-white border border-gray-300 p-4 mb-4">

    <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_auto_auto] gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Search
            </label>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search PR number, title, requester, department" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Status
            </label>

            <select name="status" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                <option value="">All Status</option>

                @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status')===$status)>
                    {{ \Illuminate\Support\Str::of($status)->replace('_', ' ')->title() }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Department
            </label>

            <select name="department" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                <option value="">All Department</option>

                @foreach ($departments as $department)
                <option value="{{ $department }}" @selected(request('department')===$department)>
                    {{ $department }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="w-full md:w-auto bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Filter
            </button>
        </div>

        <div>
            <a href="{{ route('purchasing.v2.requests.index') }}" class="block text-center bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Reset
            </a>
        </div>
    </div>
</form>

{{-- Table --}}
<div class="bg-white border border-gray-400 overflow-x-auto">
    <table class="w-full min-w-[1150px] border-collapse text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">PR No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Date</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Department</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Requester</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Request Name</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Total Items</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Priority</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Status</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Last Update</th>
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

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $purchaseRequest->items_count ?? 0 }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ \Illuminate\Support\Str::of($purchaseRequest->priority ?? '-')->replace('_', ' ')->title() }}
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
                <td colspan="11" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                    No purchase requests found.
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