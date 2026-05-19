@php
$vendorOptions = ($vendors ?? collect())->map(function ($vendor) {
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
    window.vendorOptions = @json($vendorOptions);

    document.addEventListener('DOMContentLoaded', function () {
        const vendors = window.vendorOptions || [];

        function closeAllVendorResults() {
            document.querySelectorAll('.vendor-search-results').forEach(function (box) {
                box.classList.add('hidden');
                box.innerHTML = '';
            });
        }

        function setVendorFields(field, vendor) {
            const wrapper = field.closest('.vendor-field');

            if (!wrapper) {
                return;
            }

            const searchInput = wrapper.querySelector('.vendor-search');
            const vendorIdInput = wrapper.querySelector('.vendor-id-hidden');
            const vendorNameInput = wrapper.querySelector('.vendor-name-hidden');
            const categoryInput = wrapper.querySelector('.vendor-category-hidden');
            const contactInput = wrapper.querySelector('.vendor-contact-hidden');
            const phoneInput = wrapper.querySelector('.vendor-phone-hidden');
            const emailInput = wrapper.querySelector('.vendor-email-hidden');
            const detailsBox = wrapper.querySelector('.vendor-details');

            searchInput.value = vendor.name || '';

            vendorIdInput.value = vendor.id || '';
            vendorNameInput.value = vendor.name || '';

            if (categoryInput) {
                categoryInput.value = vendor.category || '';
            }

            if (contactInput) {
                contactInput.value = vendor.contact_person || '';
            }

            if (phoneInput) {
                phoneInput.value = vendor.phone || '';
            }

            if (emailInput) {
                emailInput.value = vendor.email || '';
            }

            if (detailsBox) {
                const details = [];

                if (vendor.phone) {
                    details.push(vendor.phone);
                }

                if (vendor.email) {
                    details.push(vendor.email);
                }

                detailsBox.innerHTML = details.join(' | ');
            }

            closeAllVendorResults();
        }

        function prepareNewVendor(field) {
            const wrapper = field.closest('.vendor-field');

            if (!wrapper) {
                return;
            }

            const vendorIdInput = wrapper.querySelector('.vendor-id-hidden');
            const vendorNameInput = wrapper.querySelector('.vendor-name-hidden');
            const categoryInput = wrapper.querySelector('.vendor-category-hidden');
            const contactInput = wrapper.querySelector('.vendor-contact-hidden');
            const phoneInput = wrapper.querySelector('.vendor-phone-hidden');
            const emailInput = wrapper.querySelector('.vendor-email-hidden');
            const detailsBox = wrapper.querySelector('.vendor-details');

            const typedName = field.value.trim();

            vendorIdInput.value = '';
            vendorNameInput.value = typedName;

            if (categoryInput) {
                categoryInput.value = '';
            }

            if (contactInput) {
                contactInput.value = '';
            }

            if (phoneInput) {
                phoneInput.value = '';
            }

            if (emailInput) {
                emailInput.value = '';
            }

            if (detailsBox) {
                detailsBox.innerHTML = typedName
                    ? 'New vendor will be saved automatically.'
                    : '';
            }
        }

        function renderVendorResults(input) {
            const wrapper = input.closest('.vendor-field');
            const resultsBox = wrapper.querySelector('.vendor-search-results');
            const keyword = input.value.toLowerCase().trim();

            resultsBox.innerHTML = '';

            prepareNewVendor(input);

            if (keyword.length < 1) {
                resultsBox.classList.add('hidden');
                return;
            }

            const filteredVendors = vendors
                .filter(function (vendor) {
                    return (vendor.name || '').toLowerCase().includes(keyword);
                })
                .slice(0, 30);

            if (filteredVendors.length === 0) {
                resultsBox.innerHTML = `
                    <div class="px-3 py-2 text-gray-700 bg-gray-50">
                        New vendor: <strong>${input.value}</strong><br>
                        <span class="text-[11px] text-gray-500">
                            This vendor will be saved when you click Save Bids.
                        </span>
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
                        ${vendor.phone || ''}${vendor.phone && vendor.email ? ' | ' : ''}${vendor.email || ''}
                    </div>
                `;

                option.addEventListener('click', function () {
                    setVendorFields(input, vendor);
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

        document.addEventListener('blur', function (event) {
            if (event.target.classList.contains('vendor-search')) {
                prepareNewVendor(event.target);
            }
        }, true);

        document.addEventListener('click', function (event) {
            if (
                !event.target.closest('.vendor-search-results') &&
                !event.target.closest('.vendor-search')
            ) {
                closeAllVendorResults();
            }
        });
    });
</script>