@extends('layouts.purchasing-lite')

@section('title', 'Create Request - Nandini Purchasing Lite')

@section('content')
<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Create Purchase Request
        </h2>

        <p class="text-sm text-gray-600">
            Fill the request like a simple purchasing sheet
        </p>
    </div>

    <div>
        <a href="{{ route('purchasing.v2.requests.index') }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
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

<form method="POST" action="{{ route('purchasing.v2.requests.store') }}">
    @csrf

    {{-- Request Information --}}
    <div class="bg-white border border-gray-300 p-4 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-3">
            Request Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Requester Name
                </label>

                <input type="text" name="requester_name" value="{{ old('requester_name', $user->name ?? '') }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Department
                </label>

                <input type="text" name="department_name" value="{{ old('department_name', $user->department_name ?? '') }}" readonly class="w-full border border-gray-400 px-3 py-2 text-sm bg-gray-100 text-gray-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Priority
                </label>

                <select name="priority" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-gray-700">
                    <option value="normal" @selected(old('priority', 'normal' )==='normal' )>Normal</option>
                    <option value="high" @selected(old('priority')==='high' )>High</option>
                    <option value="urgent" @selected(old('priority')==='urgent' )>Urgent</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">
                    Date Needed
                </label>

                <input type="date" name="date_needed" value="{{ old('date_needed') }}" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Request Name
            </label>

            <input type="text" name="title" value="{{ old('title') }}" placeholder="Example: Engineering maintenance supplies" class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
        </div>

        <div class="mt-4">
            <label class="block text-xs font-bold text-gray-700 mb-1">
                Remarks
            </label>

            <textarea name="request_notes" rows="3" placeholder="Write notes or explanation here..." class="w-full border border-gray-400 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">{{ old('request_notes') }}</textarea>
        </div>
    </div>

    {{-- Items --}}
    <div class="mb-2">
        <h3 class="text-base font-bold text-gray-900">
            Requested Items
        </h3>
    </div>

    <div class="bg-white border border-gray-400 overflow-x-auto mb-3">
        <table class="w-full min-w-[1450px] border-collapse text-sm">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[50px]">No</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Item Name</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[180px]">Photos</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[100px]">Qty</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[120px]">Unit</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[160px]">Est. Unit Price</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap w-[160px]">Total Price</th>
                    <th class="border border-gray-400 px-3 py-2 text-left whitespace-nowrap">Specification</th>
                </tr>
            </thead>

            <tbody id="items-table-body">
                @for ($i = 0; $i < 5; $i++) <tr>
                    <td class="border border-gray-300 px-3 py-2 text-center row-number">
                        {{ $i + 1 }}
                    </td>

                    <td class="border border-gray-300 px-2 py-2 relative">
                        <div class="flex gap-1">
                            <div class="relative flex-1">
                                <input type="text" class="item-search w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Search item..." autocomplete="off">

                                <div class="item-search-results hidden absolute z-50 left-0 right-0 top-[34px] max-h-56 overflow-y-auto bg-white border border-gray-400 shadow text-sm"></div>
                            </div>

                            <button type="button" class="open-add-item-modal bg-gray-900 text-white border border-gray-900 px-3 py-1 text-sm font-bold hover:bg-gray-700" title="Add new item">
                                +
                            </button>
                        </div>

                        <input type="hidden" name="items[{{ $i }}][item_id]" class="item-id-hidden">
                        <input type="hidden" name="items[{{ $i }}][item_name]" class="item-name-hidden">
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <div class="item-photos-preview text-xs text-gray-400">
                            No Photo
                        </div>
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <input type="number" name="items[{{ $i }}][qty]" min="0" step="1" class="item-qty w-full border border-gray-300 px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700">
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <input type="text" name="items[{{ $i }}][unit]" value="pcs" class="item-unit w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <input type="text" inputmode="numeric" class="item-price w-full border border-gray-300 px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700">
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <input type="text" readonly class="item-total w-full border border-gray-300 px-2 py-1 text-sm text-right bg-gray-100">
                    </td>

                    <td class="border border-gray-300 px-2 py-2">
                        <input type="text" name="items[{{ $i }}][specification]" class="item-specification w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700">
                    </td>
                    </tr>
                    @endfor
            </tbody>

            <tfoot>
                <tr class="bg-gray-100">
                    <td colspan="6" class="border border-gray-400 px-3 py-2 text-right font-bold">
                        Grand Total
                    </td>

                    <td class="border border-gray-400 px-2 py-2">
                        <input type="text" id="grand_total" readonly class="w-full border border-gray-300 px-2 py-1 text-sm text-right font-bold bg-gray-100">
                    </td>

                    <td class="border border-gray-400 px-3 py-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mb-4">
        <button type="button" id="add-item-row" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            + Add Item
        </button>
    </div>

    {{-- Buttons --}}
    <div class="flex flex-wrap gap-2">
        <button type="submit" name="submit_action" value="draft" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Save Draft
        </button>

        <button type="submit" name="submit_action" value="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            Submit Request
        </button>
    </div>
</form>

{{-- Quick Add Item Modal --}}
<div id="add-item-modal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" data-close-add-item-modal></div>

    <div class="relative mx-auto mt-10 w-[95%] max-w-5xl bg-white border border-gray-400 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-300 px-4 py-3">
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    Add Item
                </h3>

                <p class="text-sm text-gray-500">
                    Add a new master item and use it directly in this purchase request
                </p>
            </div>

            <button type="button" data-close-add-item-modal class="bg-white border border-gray-400 px-3 py-1 rounded text-sm hover:bg-gray-100">
                X
            </button>
        </div>

        <div class="p-4">
            <div id="quick-item-error" class="hidden mb-4 bg-red-50 border border-red-400 text-red-700 px-4 py-3 text-sm"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Item Name</label>
                    <input type="text" id="quick_item_name" class="w-full border border-gray-400 px-3 py-2 text-sm" placeholder="Example: Cable">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">SKU</label>
                    <input type="text" id="quick_item_sku" class="w-full border border-gray-400 px-3 py-2 text-sm" placeholder="Example: ENG-CBL-001">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Category</label>
                    <input type="text" id="quick_item_category" class="w-full border border-gray-400 px-3 py-2 text-sm" placeholder="Example: Engineering">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Brand</label>
                    <input type="text" id="quick_item_brand" class="w-full border border-gray-400 px-3 py-2 text-sm" placeholder="Optional">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Default Unit</label>
                    <input type="text" id="quick_item_default_unit" class="w-full border border-gray-400 px-3 py-2 text-sm" value="pcs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Last Price</label>
                    <input type="number" id="quick_item_last_price" class="w-full border border-gray-400 px-3 py-2 text-sm text-right" placeholder="Example: 50000">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Currency</label>
                    <input type="text" id="quick_item_currency" class="w-full border border-gray-400 px-3 py-2 text-sm bg-gray-100" value="IDR">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                    <select id="quick_item_is_active" class="w-full border border-gray-400 px-3 py-2 text-sm bg-white">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Specification</label>
                <textarea id="quick_item_specification" rows="3" class="w-full border border-gray-400 px-3 py-2 text-sm" placeholder="Optional specification"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Item Photo</label>
                <input type="file" id="quick_item_photo" class="w-full border border-gray-400 px-3 py-2 text-sm" accept="image/jpeg,image/png,image/webp">
                <p class="text-xs text-gray-500 mt-1">Optional. You can upload JPG, PNG, or WebP image.</p>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-300 px-4 py-3">
            <button type="button" data-close-add-item-modal class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Cancel
            </button>

            <button type="button" id="save-quick-item" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Save Item
            </button>
        </div>
    </div>
</div>

@php
$masterItems = $items->map(function ($item) {
return [
'id' => $item->id,
'name' => $item->name,
'unit' => $item->default_unit,
'price' => $item->last_price,
'specification' => $item->default_specification,
'photos' => $item->photos->map(function ($photo) {
return [
'url' => asset('storage/' . $photo->file_path),
'file_name' => $photo->file_name,
];
})->values(),
];
})->values();
@endphp

<script>
    window.masterItems = @json($masterItems);
</script>
@endsection

@push('scripts')
<script>
    let itemIndex = document.querySelectorAll('#items-table-body tr').length;
    let activeItemRow = null;

    const masterItems = window.masterItems || [];

    function parseCurrency(value) {
        if (!value) {
            return 0;
        }

        let clean = String(value)
            .replace(/Rp/gi, '')
            .replace(/\s/g, '')
            .trim();

        if (/^\d+\.\d{2}$/.test(clean)) {
            clean = clean.split('.')[0];
        } else if (clean.includes(',')) {
            clean = clean.split(',')[0].replaceAll('.', '');
        } else {
            clean = clean.replaceAll('.', '').replaceAll(',', '');
        }

        return Number(clean) || 0;
    }

    function formatRupiah(value) {
        const number = parseCurrency(value);

        if (number <= 0) {
            return '';
        }

        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0
        }).format(number);
    }

    function calculateTotals() {
        let grandTotal = 0;

        document.querySelectorAll('#items-table-body tr').forEach(function (row) {
            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const totalInput = row.querySelector('.item-total');

            if (!qtyInput || !priceInput || !totalInput) {
                return;
            }

            const qty = Number(qtyInput.value || 0);
            const price = parseCurrency(priceInput.value);
            const total = qty * price;

            totalInput.value = total > 0 ? formatRupiah(total) : '';
            grandTotal += total;
        });

        document.getElementById('grand_total').value = grandTotal > 0
            ? formatRupiah(grandTotal)
            : '';
    }

    function closeAllItemResults() {
        document.querySelectorAll('.item-search-results').forEach(function (box) {
            box.classList.add('hidden');
            box.innerHTML = '';
        });
    }

    function renderItemPhotos(row, item) {
        const photosBox = row.querySelector('.item-photos-preview');
        const photos = item.photos || [];

        if (!photosBox) {
            return;
        }

        if (photos.length === 0) {
            photosBox.innerHTML = '<span class="text-xs text-gray-400">No Photo</span>';
            return;
        }

        let html = '<div class="flex gap-1">';

        photos.slice(0, 3).forEach(function (photo) {
            html += `
                <a href="${photo.url}" target="_blank">
                    <img
                        src="${photo.url}"
                        alt="${item.name || 'Item photo'}"
                        class="w-12 h-12 object-cover border border-gray-300 hover:opacity-80"
                    >
                </a>
            `;
        });

        if (photos.length > 3) {
            html += `
                <div class="w-12 h-12 border border-gray-300 bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">
                    +${photos.length - 3}
                </div>
            `;
        }

        html += '</div>';

        photosBox.innerHTML = html;
    }

    function renderItemResults(input) {
        const row = input.closest('tr');
        const resultsBox = row.querySelector('.item-search-results');
        const keyword = input.value.toLowerCase().trim();

        resultsBox.innerHTML = '';

        if (keyword.length < 1) {
            resultsBox.classList.add('hidden');
            return;
        }

        const filteredItems = masterItems
            .filter(function (item) {
                return (item.name || '').toLowerCase().includes(keyword);
            })
            .slice(0, 30);

        if (filteredItems.length === 0) {
            resultsBox.innerHTML = `
                <button type="button" class="quick-add-from-search block w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-100">
                    No item found. Click here to add "${input.value}"
                </button>
            `;

            resultsBox.querySelector('.quick-add-from-search').addEventListener('click', function () {
                activeItemRow = row;
                openAddItemModal(input.value);
                closeAllItemResults();
            });

            resultsBox.classList.remove('hidden');
            return;
        }

        filteredItems.forEach(function (item) {
            const option = document.createElement('button');

            option.type = 'button';
            option.className = 'block w-full text-left px-3 py-2 hover:bg-gray-100 border-b border-gray-200';
            option.innerHTML = `
                <div class="font-semibold">${item.name || '-'}</div>
                <div class="text-xs text-gray-500">
                    Unit: ${item.unit || '-'} | Last Price: ${formatRupiah(item.price) || '-'} | Photos: ${(item.photos || []).length}
                </div>
            `;

            option.addEventListener('click', function () {
                selectItem(row, item);
            });

            resultsBox.appendChild(option);
        });

        resultsBox.classList.remove('hidden');
    }

    function selectItem(row, item) {
        const searchInput = row.querySelector('.item-search');
        const itemIdInput = row.querySelector('.item-id-hidden');
        const itemNameInput = row.querySelector('.item-name-hidden');
        const unitInput = row.querySelector('.item-unit');
        const priceInput = row.querySelector('.item-price');
        const specificationInput = row.querySelector('.item-specification');

        searchInput.value = item.name || '';
        itemIdInput.value = item.id || '';
        itemNameInput.value = item.name || '';
        unitInput.value = item.unit || 'pcs';
        priceInput.value = item.price ? formatRupiah(item.price) : '';
        specificationInput.value = item.specification || '';

        renderItemPhotos(row, item);
        closeAllItemResults();
        calculateTotals();
    }

    function createItemRow(index) {
        return `
            <tr>
                <td class="border border-gray-300 px-3 py-2 text-center row-number">
                    ${index + 1}
                </td>

                <td class="border border-gray-300 px-2 py-2 relative">
                    <div class="flex gap-1">
                        <div class="relative flex-1">
                            <input
                                type="text"
                                class="item-search w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700"
                                placeholder="Search item..."
                                autocomplete="off"
                            >

                            <div class="item-search-results hidden absolute z-50 left-0 right-0 top-[34px] max-h-56 overflow-y-auto bg-white border border-gray-400 shadow text-sm"></div>
                        </div>

                        <button
                            type="button"
                            class="open-add-item-modal bg-gray-900 text-white border border-gray-900 px-3 py-1 text-sm font-bold hover:bg-gray-700"
                            title="Add new item"
                        >
                            +
                        </button>
                    </div>

                    <input
                        type="hidden"
                        name="items[${index}][item_id]"
                        class="item-id-hidden"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][item_name]"
                        class="item-name-hidden"
                    >
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <div class="item-photos-preview text-xs text-gray-400">
                        No Photo
                    </div>
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <input
                        type="number"
                        name="items[${index}][qty]"
                        min="0"
                        step="1"
                        class="item-qty w-full border border-gray-300 px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700"
                    >
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <input
                        type="text"
                        name="items[${index}][unit]"
                        value="pcs"
                        class="item-unit w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700"
                    >
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <input
                        type="text"
                        inputmode="numeric"
                        class="item-price w-full border border-gray-300 px-2 py-1 text-sm text-right focus:outline-none focus:ring-1 focus:ring-gray-700"
                    >
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <input
                        type="text"
                        readonly
                        class="item-total w-full border border-gray-300 px-2 py-1 text-sm text-right bg-gray-100"
                    >
                </td>

                <td class="border border-gray-300 px-2 py-2">
                    <input
                        type="text"
                        name="items[${index}][specification]"
                        class="item-specification w-full border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700"
                    >
                </td>
            </tr>
        `;
    }

    function openAddItemModal(defaultName = '') {
        document.getElementById('quick-item-error').classList.add('hidden');
        document.getElementById('quick-item-error').innerHTML = '';

        document.getElementById('quick_item_name').value = defaultName || '';
        document.getElementById('quick_item_sku').value = '';
        document.getElementById('quick_item_category').value = '';
        document.getElementById('quick_item_brand').value = '';
        document.getElementById('quick_item_default_unit').value = 'pcs';
        document.getElementById('quick_item_last_price').value = '';
        document.getElementById('quick_item_currency').value = 'IDR';
        document.getElementById('quick_item_is_active').value = '1';
        document.getElementById('quick_item_specification').value = '';
        document.getElementById('quick_item_photo').value = '';

        document.getElementById('add-item-modal').classList.remove('hidden');

        setTimeout(function () {
            document.getElementById('quick_item_name').focus();
        }, 50);
    }

    function closeAddItemModal() {
        document.getElementById('add-item-modal').classList.add('hidden');
    }

    function normalizeQuickItem(item) {
        return {
            id: item.id,
            name: item.name,
            unit: item.default_unit || item.unit || 'pcs',
            price: item.last_price || item.price || 0,
            specification: item.default_specification || item.specification || '',
            photos: item.photos || [],
        };
    }

    document.getElementById('add-item-row').addEventListener('click', function () {
        const tableBody = document.getElementById('items-table-body');

        tableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));

        itemIndex++;
    });

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('item-search')) {
            const row = event.target.closest('tr');

            row.querySelector('.item-id-hidden').value = '';
            row.querySelector('.item-name-hidden').value = event.target.value;

            renderItemResults(event.target);
        }

        if (
            event.target.classList.contains('item-qty') ||
            event.target.classList.contains('item-price')
        ) {
            calculateTotals();
        }
    });

    document.addEventListener('blur', function (event) {
        if (event.target.classList.contains('item-price')) {
            const price = parseCurrency(event.target.value);

            event.target.value = price > 0 ? formatRupiah(price) : '';

            calculateTotals();
        }
    }, true);

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('open-add-item-modal')) {
            const row = event.target.closest('tr');
            const searchInput = row.querySelector('.item-search');

            activeItemRow = row;
            openAddItemModal(searchInput.value);
        }

        if (!event.target.closest('.item-search-results') && !event.target.closest('.item-search')) {
            closeAllItemResults();
        }

        if (event.target.hasAttribute('data-close-add-item-modal')) {
            closeAddItemModal();
        }
    });

    document.getElementById('save-quick-item').addEventListener('click', async function () {
        const button = this;
        const errorBox = document.getElementById('quick-item-error');

        errorBox.classList.add('hidden');
        errorBox.innerHTML = '';

        const formData = new FormData();
        formData.append('name', document.getElementById('quick_item_name').value);
        formData.append('sku', document.getElementById('quick_item_sku').value);
        formData.append('category', document.getElementById('quick_item_category').value);
        formData.append('brand', document.getElementById('quick_item_brand').value);
        formData.append('default_unit', document.getElementById('quick_item_default_unit').value);
        formData.append('last_price', document.getElementById('quick_item_last_price').value);
        formData.append('currency', document.getElementById('quick_item_currency').value);
        formData.append('is_active', document.getElementById('quick_item_is_active').value);
        formData.append('default_specification', document.getElementById('quick_item_specification').value);

        const photo = document.getElementById('quick_item_photo').files[0];

        if (photo) {
            formData.append('photo', photo);
        }

        button.disabled = true;
        button.innerText = 'Saving...';

        try {
            const response = await fetch('{{ route('purchasing.v2.items.quick-store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                let message = 'Failed to save item.';

                if (data.errors) {
                    message = Object.values(data.errors).flat().join('<br>');
                } else if (data.message) {
                    message = data.message;
                }

                errorBox.innerHTML = message;
                errorBox.classList.remove('hidden');
                return;
            }

            const newItem = normalizeQuickItem(data.item);

            masterItems.push(newItem);

            if (activeItemRow) {
                selectItem(activeItemRow, newItem);
            }

            closeAddItemModal();
        } catch (error) {
            errorBox.innerHTML = 'Failed to save item. Please try again.';
            errorBox.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.innerText = 'Save Item';
        }
    });
</script>
@endpush