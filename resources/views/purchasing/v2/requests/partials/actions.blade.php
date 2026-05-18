@php
$userRole = strtolower((string) (auth()->user()->role ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$purchasingEditableStatuses = [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
'on_hold_by_gm',
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

$isGmUser = in_array($normalizedRole, [
'admin',
'gm',
'general manager',
], true);

$canGmApproveItems = $isGmUser
&& $purchaseRequest->status === 'submitted_to_gm';

$allItemsHaveSelectedVendor = $purchaseRequest->items->every(function ($item) {
return $item->vendorOffers->contains('is_selected_by_accounting', true);
});
@endphp

<div class="bg-white border border-gray-300 p-4">
    <div class="text-sm font-bold text-gray-900 mb-3">
        Actions
    </div>

    @if ($purchaseRequest->status === 'draft')
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('purchasing.v2.requests.submit', $purchaseRequest) }}" onsubmit="return confirm('Submit this purchase request?')">
            @csrf

            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit Request
            </button>
        </form>

        <a href="#" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Edit Draft
        </a>
    </div>

    @elseif ($canEditVendorOffers)
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('purchasing.v2.requests.submit-to-accounting', $purchaseRequest) }}" onsubmit="return confirm('Submit this purchase request to Accounting?')">
            @csrf

            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit to Accounting
            </button>
        </form>

        <a href="#" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Return to Requester
        </a>
    </div>

    @elseif ($canSubmitToGm)
    <div class="flex flex-wrap gap-2">
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
    </div>

    @elseif ($canGmApproveItems)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <form method="POST" action="{{ route('purchasing.v2.requests.gm-send-back-to-purchasing', $purchaseRequest) }}" class="border border-gray-300 bg-gray-50 p-4" onsubmit="return confirm('Send this purchase request back to Purchasing?')">
            @csrf

            <div class="mb-3 text-sm font-bold text-gray-900">
                Send Back to Purchasing
            </div>

            <label class="block text-xs font-bold text-gray-700 mb-1">
                Message to Purchasing
            </label>

            <textarea name="message" rows="4" class="w-full border border-gray-300 px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Explain what Purchasing needs to revise..." required>{{ old('message') }}</textarea>

            <button type="submit" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                Send Back to Purchasing
            </button>
        </form>

        <form method="POST" action="{{ route('purchasing.v2.requests.gm-reject', $purchaseRequest) }}" class="border border-red-300 bg-red-50 p-4" onsubmit="return confirm('Reject this purchase request?')">
            @csrf

            <div class="mb-3 text-sm font-bold text-red-700">
                Reject PR
            </div>

            <label class="block text-xs font-bold text-red-700 mb-1">
                Reject Message
            </label>

            <textarea name="message" rows="4" class="w-full border border-red-300 px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-1 focus:ring-red-700" placeholder="Explain why this PR is rejected..." required>{{ old('message') }}</textarea>

            <button type="submit" class="bg-red-600 text-white border border-red-600 px-4 py-2 rounded text-sm hover:bg-red-700">
                Reject PR
            </button>
        </form>
    </div>

    @else
    <div class="flex flex-wrap gap-2">
        <span class="inline-block bg-gray-100 text-gray-500 border border-gray-300 px-4 py-2 rounded text-sm">
            No action available
        </span>
    </div>
    @endif

    @if (! in_array($purchaseRequest->status, ['rejected', 'cancelled', 'gm_approved'], true))
    <div class="mt-4">
        <a href="#" class="inline-block bg-white text-red-700 border border-red-400 px-4 py-2 rounded text-sm hover:bg-red-50">
            Cancel
        </a>
    </div>
    @endif
</div>