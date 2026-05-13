@extends('layouts.purchasing-lite')

@section('title', 'Item Master - Nandini Purchasing Lite')

@section('content')
@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-600 text-green-700 px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Item Master
        </h2>

        <p class="text-sm text-gray-600">
            Master item list used for purchase requests
        </p>
    </div>

    <div>
        <a href="{{ route('purchasing.v2.items.create') }}" class="inline-block bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            + Add Item
        </a>
    </div>
</div>

<form method="GET" action="{{ route('purchasing.v2.items.index') }}" class="bg-white border border-gray-300 p-4 mb-4">

    <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_auto_auto] gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Search
            </label>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item name, SKU, category, brand" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
        </div>

        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Status
            </label>

            <select name="status" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                <option value="">All Status</option>
                <option value="active" @selected(request('status')==='active' )>Active</option>
                <option value="inactive" @selected(request('status')==='inactive' )>Inactive</option>
            </select>
        </div>

        <div>
            <button type="submit" class="w-full md:w-auto bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Filter
            </button>
        </div>

        <div>
            <a href="{{ route('purchasing.v2.items.index') }}" class="block text-center bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Reset
            </a>
        </div>
    </div>
</form>

<div class="bg-white border border-gray-400 overflow-x-auto">
    <table class="w-full min-w-[1200px] border-collapse text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">No</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Photos</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Item Name</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">SKU</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Category</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Brand</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Unit</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Last Price</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Currency</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Status</th>
                <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($items as $item)
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    @if ($item->photos->isNotEmpty())
                    <div class="flex gap-1">
                        @foreach ($item->photos->take(4) as $photo)
                        <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $item->name }}" class="w-12 h-12 object-cover border border-gray-300 hover:opacity-80">
                        </a>
                        @endforeach

                        @if ($item->photos->count() > 4)
                        <div class="w-12 h-12 border border-gray-300 bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                            +{{ $item->photos->count() - 4 }}
                        </div>
                        @endif
                    </div>
                    @else
                    <span class="text-xs text-gray-400">
                        No Photo
                    </span>
                    @endif
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap font-semibold">
                    {{ $item->name ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $item->sku ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $item->category ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $item->brand ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $item->default_unit ?? '-' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap text-right">
                    @if ($item->last_price)
                    Rp {{ number_format((float) $item->last_price, 0, ',', '.') }}
                    @else
                    -
                    @endif
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    {{ $item->currency ?? 'IDR' }}
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    @if ($item->is_active)
                    <span class="inline-block border border-green-600 bg-green-50 text-green-700 px-2 py-1 text-xs font-bold rounded">
                        Active
                    </span>
                    @else
                    <span class="inline-block border border-red-600 bg-red-50 text-red-700 px-2 py-1 text-xs font-bold rounded">
                        Inactive
                    </span>
                    @endif
                </td>

                <td class="border border-gray-300 px-3 py-2 whitespace-nowrap">
                    <a href="{{ route('purchasing.v2.items.edit', $item) }}" class="inline-block bg-gray-900 text-white px-3 py-1 rounded text-xs hover:bg-gray-700">
                        Edit
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="border border-gray-300 px-3 py-6 text-center text-gray-500">
                    No items found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $items->links() }}
</div>
@endsection