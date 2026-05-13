@php
$userRole = strtolower((string) (auth()->user()->role ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$purchasingEditableStatuses = [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
];

$canEditVendorOffers = in_array($normalizedRole, ['purchasing', 'admin'], true)
&& in_array($purchaseRequest->status, $purchasingEditableStatuses, true);

$isCostControlUser = in_array($normalizedRole, [
'admin',
'accounting',
'cost control',
'cost controller',
], true);

$canSubmitToGm = $isCostControlUser
&& $purchaseRequest->status === 'submitted_to_accounting';

$allItemsHaveSelectedVendor = $purchaseRequest->items->every(function ($item) {
return $item->vendorOffers->contains('is_selected_by_accounting', true);
});
@endphp

<div class="bg-white border border-gray-300 p-4">
    <div class="text-sm font-bold text-gray-900 mb-3">
        Actions
    </div>

    <div class="flex flex-wrap gap-2">
        @if ($purchaseRequest->status === 'draft')
        <form method="POST" action="{{ route('purchasing.v2.requests.submit', $purchaseRequest) }}" onsubmit="return confirm('Submit this purchase request?')">
            @csrf

            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit Request
            </button>
        </form>

        <a href="#" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Edit Draft
        </a>
        @elseif ($canEditVendorOffers)
        <form method="POST" action="{{ route('purchasing.v2.requests.submit-to-accounting', $purchaseRequest) }}" onsubmit="return confirm('Submit this purchase request to Accounting?')">
            @csrf

            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit to Accounting
            </button>
        </form>

        <a href="#" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Return to Requester
        </a>
        @elseif ($canSubmitToGm)
        <form method="POST" action="{{ route('purchasing.v2.requests.submit-to-gm', $purchaseRequest) }}" onsubmit="return confirm('Submit this purchase request to GM?')">
            @csrf

            <button type="submit" @disabled(! $allItemsHaveSelectedVendor) class="px-4 py-2 rounded text-sm
                        {{ $allItemsHaveSelectedVendor
                            ? 'bg-gray-900 text-white hover:bg-gray-700'
                            : 'bg-gray-100 text-gray-500 border border-gray-300 cursor-not-allowed' }}">
                Submit to GM
            </button>
        </form>

        @if (! $allItemsHaveSelectedVendor)
        <span class="inline-block bg-yellow-50 text-yellow-700 border border-yellow-600 px-4 py-2 rounded text-sm">
            Select one vendor for every item first.
        </span>
        @endif
        @else
        <span class="inline-block bg-gray-100 text-gray-500 border border-gray-300 px-4 py-2 rounded text-sm">
            No action available
        </span>
        @endif

        <a href="#" class="inline-block bg-white text-red-700 border border-red-400 px-4 py-2 rounded text-sm hover:bg-red-50">
            Cancel
        </a>
    </div>
</div>