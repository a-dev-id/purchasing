@if ($canEditVendorOffers)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const priceInputs = document.querySelectorAll('.vendor-price-input');

        function onlyDigits(value) {
            return String(value || '').replace(/[^0-9]/g, '');
        }

        function formatRupiah(value) {
            const number = onlyDigits(value);

            if (! number) {
                return '';
            }

            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(number));
        }

        priceInputs.forEach(function (input) {
            input.value = formatRupiah(input.value);

            input.addEventListener('input', function () {
                input.value = formatRupiah(input.value);
                input.setSelectionRange(input.value.length, input.value.length);
            });

            input.addEventListener('blur', function () {
                input.value = formatRupiah(input.value);
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                priceInputs.forEach(function (input) {
                    input.value = onlyDigits(input.value);
                });
            });
        });
    });
</script>
@endif

@if ($canGmApproveItems)
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.gm-approve-checkbox');

        checkboxes.forEach(function (checkbox) {
            const itemId = checkbox.dataset.itemId;

            const reasonWrap = document.querySelector(
                '.gm-not-approved-reason-wrap[data-item-id="' + itemId + '"]'
            );

            const reasonSelect = document.querySelector(
                '.gm-not-approved-reason[data-item-id="' + itemId + '"]'
            );

            const reasonDetailWrap = document.querySelector(
                '.gm-not-approved-reason-detail-wrap[data-item-id="' + itemId + '"]'
            );

            const reasonDetailTextarea = document.querySelector(
                '.gm-not-approved-reason-detail[data-item-id="' + itemId + '"]'
            );

            function toggleReasonDetail() {
                if (! reasonSelect || ! reasonDetailWrap || ! reasonDetailTextarea) {
                    return;
                }

                if (reasonSelect.value === 'Reason' && ! checkbox.checked) {
                    reasonDetailWrap.classList.remove('hidden');
                    reasonDetailTextarea.disabled = false;
                    reasonDetailTextarea.required = true;
                } else {
                    reasonDetailWrap.classList.add('hidden');
                    reasonDetailTextarea.value = '';
                    reasonDetailTextarea.disabled = true;
                    reasonDetailTextarea.required = false;
                }
            }

            function toggleReasonDropdown() {
                if (! reasonWrap || ! reasonSelect) {
                    return;
                }

                if (checkbox.checked) {
                    reasonWrap.classList.add('hidden');
                    reasonSelect.value = '';
                    reasonSelect.disabled = true;
                    reasonSelect.required = false;
                } else {
                    reasonWrap.classList.remove('hidden');
                    reasonSelect.disabled = false;
                    reasonSelect.required = true;
                }

                toggleReasonDetail();
            }

            checkbox.addEventListener('change', toggleReasonDropdown);

            if (reasonSelect) {
                reasonSelect.addEventListener('change', toggleReasonDetail);
            }

            toggleReasonDropdown();
        });
    });
</script>
@endif