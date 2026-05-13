@php
$masterVendors = ($vendors ?? collect())->map(function ($vendor) {
return [
'id' => $vendor->id,
'name' => $vendor->name,
'category' => $vendor->category,
'contact_person' => $vendor->contact_person,
'phone' => $vendor->phone,
'email' => $vendor->email,
];
})->values();
@endphp

<script>
    window.masterVendors = @json($masterVendors);

    function closeAllVendorResults() {
        document.querySelectorAll('.vendor-search-results').forEach(function (box) {
            box.classList.add('hidden');
            box.innerHTML = '';
        });
    }

    function selectVendor(container, vendor) {
        const searchInput = container.querySelector('.vendor-search');
        const vendorIdInput = container.querySelector('.vendor-id-hidden');
        const vendorNameInput = container.querySelector('.vendor-name-hidden');
        const categoryInput = container.querySelector('.vendor-category-hidden');
        const contactInput = container.querySelector('.vendor-contact-hidden');
        const phoneInput = container.querySelector('.vendor-phone-hidden');
        const emailInput = container.querySelector('.vendor-email-hidden');
        const detailsBox = container.querySelector('.vendor-details');

        searchInput.value = vendor.name || '';
        vendorIdInput.value = vendor.id || '';
        vendorNameInput.value = vendor.name || '';
        categoryInput.value = vendor.category || '';
        contactInput.value = vendor.contact_person || '';
        phoneInput.value = vendor.phone || '';
        emailInput.value = vendor.email || '';

        if (detailsBox) {
            const details = [vendor.phone, vendor.email].filter(Boolean).join(' | ');
            detailsBox.innerHTML = details;
        }

        closeAllVendorResults();
    }

    function renderVendorResults(input) {
        const container = input.closest('.vendor-field');
        const resultsBox = container.querySelector('.vendor-search-results');
        const keyword = input.value.toLowerCase().trim();
        const vendors = window.masterVendors || [];

        const vendorNameInput = container.querySelector('.vendor-name-hidden');
        const vendorIdInput = container.querySelector('.vendor-id-hidden');

        vendorNameInput.value = input.value;
        vendorIdInput.value = '';

        resultsBox.innerHTML = '';

        if (keyword.length < 1) {
            resultsBox.classList.add('hidden');
            return;
        }

        const filteredVendors = vendors
            .filter(function (vendor) {
                return (
                    (vendor.name || '').toLowerCase().includes(keyword) ||
                    (vendor.category || '').toLowerCase().includes(keyword) ||
                    (vendor.phone || '').toLowerCase().includes(keyword)
                );
            })
            .slice(0, 30);

        if (filteredVendors.length === 0) {
            resultsBox.innerHTML = `
                <div class="px-3 py-2 text-gray-500">
                    No vendor found
                </div>
            `;

            resultsBox.classList.remove('hidden');
            return;
        }

        filteredVendors.forEach(function (vendor) {
            const option = document.createElement('button');

            option.type = 'button';
            option.className = 'block w-full text-left px-3 py-2 hover:bg-gray-100 border-b border-gray-200';
            option.innerHTML = `
                <div class="font-semibold">${vendor.name || '-'}</div>
                <div class="text-[11px] text-gray-500">
                    ${vendor.category || '-'} ${vendor.phone ? '| ' + vendor.phone : ''}
                </div>
            `;

            option.addEventListener('click', function () {
                selectVendor(container, vendor);
            });

            resultsBox.appendChild(option);
        });

        resultsBox.classList.remove('hidden');
    }

    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('vendor-search')) {
            renderVendorResults(event.target);
        }
    });

    document.addEventListener('click', function (event) {
        if (! event.target.closest('.vendor-search-results') && ! event.target.closest('.vendor-search')) {
            closeAllVendorResults();
        }
    });
</script>