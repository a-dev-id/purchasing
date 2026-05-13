@extends('layouts.purchasing-lite')

@section('title', 'Edit Item - Nandini Purchasing Lite')

@section('content')
@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-600 text-green-700 px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Edit Item
        </h2>

        <p class="text-sm text-gray-600">
            Update master item information
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

<form method="POST" action="{{ route('purchasing.v2.items.update', $item) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="bg-white border border-gray-300 p-4 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-3">
            Item Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Item Name
                </label>

                <input type="text" name="name" value="{{ old('name', $item->name) }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    SKU
                </label>

                <input type="text" name="sku" value="{{ old('sku', $item->sku) }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Category
                </label>

                <input type="text" name="category" value="{{ old('category', $item->category) }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Brand
                </label>

                <input type="text" name="brand" value="{{ old('brand', $item->brand) }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Default Unit
                </label>

                <input type="text" name="default_unit" value="{{ old('default_unit', $item->default_unit ?: 'pcs') }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Last Price
                </label>

                <input type="number" name="last_price" value="{{ old('last_price', $item->last_price) }}" min="0" step="1" class="w-full border border-gray-400 px-3 py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Currency
                </label>

                <input type="text" name="currency" value="{{ old('currency', $item->currency ?: 'IDR') }}" readonly class="w-full border border-gray-400 px-3 py-2 text-sm bg-gray-100 text-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Status
                </label>

                <select name="is_active" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                    <option value="1" @selected(old('is_active', $item->is_active ? '1' : '0') == '1')>
                        Active
                    </option>

                    <option value="0" @selected(old('is_active', $item->is_active ? '1' : '0') == '0')>
                        Inactive
                    </option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Default Specification
            </label>

            <textarea name="default_specification" rows="4" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">{{ old('default_specification', $item->default_specification) }}</textarea>
        </div>

        <div class="mt-4 border-t border-gray-200 pt-4">
            <label class="block text-xs font-bold text-gray-700 mb-2">
                Current Photos
            </label>

            @if ($item->photos->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach ($item->photos as $photo)
                <div class="border border-gray-300 p-2 bg-white">
                    <a href="{{ asset('storage/' . $photo->file_path) }}" target="_blank">
                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $item->name }}" class="w-20 h-20 object-cover border border-gray-300 hover:opacity-80">
                    </a>

                    <button type="submit" form="delete-photo-{{ $photo->id }}" onclick="return confirm('Delete this photo?')" class="mt-2 w-full bg-white text-red-700 border border-red-400 px-2 py-1 rounded text-xs hover:bg-red-50">
                        Delete
                    </button>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-sm text-gray-500 mb-4">
                No photos uploaded yet.
            </div>
            @endif

            <label class="block text-xs font-bold text-gray-700 mb-1">
                Upload More Photos
            </label>

            <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">

            <p class="text-xs text-gray-500 mt-1">
                Optional. You can upload JPG, PNG, or WebP images.
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            Update Item
        </button>

        <a href="{{ route('purchasing.v2.items.index') }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Cancel
        </a>
    </div>
</form>

@foreach ($item->photos as $photo)
<form id="delete-photo-{{ $photo->id }}" method="POST" action="{{ route('purchasing.v2.items.photos.destroy', $photo) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endforeach
@endsection