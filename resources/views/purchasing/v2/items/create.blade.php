@extends('layouts.purchasing-lite')

@section('title', 'Add Item - Nandini Purchasing Lite')

@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Add Item
        </h2>

        <p class="text-sm text-gray-600">
            Add a new master item for purchase requests
        </p>
    </div>

    <div>
        <a href="{{ route('purchasing.v2.items.index') }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Back
        </a>
    </div>
</div>

@if ($errors->any())
<div class="mb-4 bg-red-50 border border-red-600 text-red-700 px-4 py-3 text-sm">
    <div class="font-bold mb-2">
        Please fix the following:
    </div>

    <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('purchasing.v2.items.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="bg-white border border-gray-300 p-4 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-3">
            Item Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Item Name
                </label>

                <input type="text" name="name" value="{{ old('name') }}" placeholder="Example: Cable" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    SKU
                </label>

                <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Example: ENG-CBL-001" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Category
                </label>

                <input type="text" name="category" value="{{ old('category') }}" placeholder="Example: Engineering" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Brand
                </label>

                <input type="text" name="brand" value="{{ old('brand') }}" placeholder="Optional" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Default Unit
                </label>

                <input type="text" name="default_unit" value="{{ old('default_unit', 'pcs') }}" placeholder="pcs, box, roll, kg" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Last Price
                </label>

                <input type="number" name="last_price" value="{{ old('last_price') }}" min="0" step="1" placeholder="Example: 50000" class="w-full border border-gray-400 px-3 py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Currency
                </label>

                <input type="text" name="currency" value="{{ old('currency', 'IDR') }}" readonly class="w-full border border-gray-400 px-3 py-2 text-sm bg-gray-100 text-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Status
                </label>

                <select name="is_active" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                    <option value="1" @selected(old('is_active', '1' )=='1' )>Active</option>
                    <option value="0" @selected(old('is_active')=='0' )>Inactive</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Item Photo
            </label>

            <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">

            <p class="text-xs text-gray-500 mt-1">
                Optional. You can upload JPG, PNG, or WebP images.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            Save Item
        </button>

        <a href="{{ route('purchasing.v2.items.index') }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Cancel
        </a>
    </div>
</form>
@endsection