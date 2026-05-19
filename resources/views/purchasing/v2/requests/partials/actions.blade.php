@php
$user = auth()->user();

$userRole = strtolower((string) ($user->role ?? ''));
$normalizedRole = str_replace(['-', '_'], ' ', $userRole);

$isRequester = $normalizedRole === 'requester';
$isAdmin = $normalizedRole === 'admin';

$isPurchasing = in_array($normalizedRole, [
'purchasing',
'purchase',
'purchasing staff',
], true);

$isOwnerOrSameDepartment =
(int) $purchaseRequest->requested_by === (int) $user->id
|| (
! empty($user->department_name)
&& $purchaseRequest->department_name === $user->department_name
);

$canEditDraft = $purchaseRequest->status === 'draft'
&& (
$isAdmin
|| $isOwnerOrSameDepartment
);

$requesterEditableStatuses = [
'revision_from_purchasing',
'revision_from_accounting',
'revision_from_gm',
'revision_to_requester_from_accounting',
'revision_to_requester_from_gm',
];

$canEditReturnedRequest = in_array($purchaseRequest->status, $requesterEditableStatuses, true)
&& (
$isAdmin
|| (
$isRequester
&& $isOwnerOrSameDepartment
)
);

$canEditRequest = $canEditDraft || $canEditReturnedRequest;

$canSubmitDraft = $canEditDraft && $purchaseRequest->status === 'draft';

$purchasingActionStatuses = [
'submitted',
'revision_to_purchasing_from_accounting',
'revision_to_purchasing_from_gm',
'on_hold_by_gm',
];

$canPurchasingAction = ($isAdmin || $isPurchasing)
&& in_array($purchaseRequest->status, $purchasingActionStatuses, true);

$canSubmitToAccounting = $canPurchasingAction;
$canReturnToRequester = $canPurchasingAction;
$canRejectRequest = $canPurchasingAction;

$showActions = $canEditRequest
|| $canSubmitDraft
|| $canSubmitToAccounting
|| $canReturnToRequester
|| $canRejectRequest;
@endphp

@if ($showActions)
<div class="bg-white border border-gray-300 p-4 mt-4">
    <h3 class="text-base font-bold text-gray-900 mb-4">
        Actions
    </h3>

    <div class="flex flex-wrap gap-2">
        @if ($canSubmitDraft)
        <form method="POST" action="{{ route('purchasing.v2.requests.submit', $purchaseRequest) }}">
            @csrf

            <button type="submit" onclick="return confirm('Submit this purchase request to Purchasing?')" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit Request
            </button>
        </form>
        @endif

        @if ($canEditRequest)
        <a href="{{ route('purchasing.v2.requests.edit', $purchaseRequest) }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Edit Request
        </a>
        @endif

        @if ($canSubmitToAccounting)
        <form method="POST" action="{{ route('purchasing.v2.requests.submit-to-accounting', $purchaseRequest) }}">
            @csrf

            <button type="submit" onclick="return confirm('Submit this purchase request to Accounting / Cost Control? Please make sure all vendor prices are saved first.')" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                Submit to Accounting
            </button>
        </form>
        @endif

        @if ($canReturnToRequester)
        <button type="button" data-pr-modal-open="return-to-requester-modal" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Send Back to Requester
        </button>
        @endif

        @if ($canRejectRequest)
        <button type="button" data-pr-modal-open="reject-request-modal" class="bg-white text-red-600 border border-red-500 px-4 py-2 rounded text-sm hover:bg-red-50">
            Reject
        </button>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('purchasing.v2.requests.index') }}" class="inline-block bg-white text-red-600 border border-red-500 px-4 py-2 rounded text-sm hover:bg-red-50">
            Cancel
        </a>
    </div>
</div>
@endif

@if ($canReturnToRequester)
<div id="return-to-requester-modal" class="hidden fixed inset-0 z-50 bg-black/40 items-center justify-center px-4">
    <div class="bg-white w-full max-w-lg border border-gray-300 shadow-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="text-base font-bold text-gray-900">
                Send Back to Requester
            </h3>

            <button type="button" data-pr-modal-close="return-to-requester-modal" class="text-gray-500 hover:text-gray-900 text-xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('purchasing.v2.requests.return-to-requester', $purchaseRequest) }}">
            @csrf

            <div class="p-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Remark / Message
                </label>

                <textarea name="remark" rows="5" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Write the reason why this PR is returned to requester...">{{ old('remark') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-4 py-3">
                <button type="button" data-pr-modal-close="return-to-requester-modal" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                    Cancel
                </button>

                <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                    Submit Return
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if ($canRejectRequest)
<div id="reject-request-modal" class="hidden fixed inset-0 z-50 bg-black/40 items-center justify-center px-4">
    <div class="bg-white w-full max-w-lg border border-gray-300 shadow-lg">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="text-base font-bold text-gray-900">
                Reject Purchase Request
            </h3>

            <button type="button" data-pr-modal-close="reject-request-modal" class="text-gray-500 hover:text-gray-900 text-xl leading-none">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('purchasing.v2.requests.reject', $purchaseRequest) }}">
            @csrf

            <div class="p-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Rejection Message
                </label>

                <textarea name="remark" rows="5" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-700" placeholder="Write the reason why this PR is rejected...">{{ old('remark') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 px-4 py-3">
                <button type="button" data-pr-modal-close="reject-request-modal" class="bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
                    Cancel
                </button>

                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                    Submit Reject
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function openModal(id) {
            const modal = document.getElementById(id);

            if (! modal) {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);

            if (! modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.querySelectorAll('[data-pr-modal-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(button.dataset.prModalOpen);
            });
        });

        document.querySelectorAll('[data-pr-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.dataset.prModalClose);
            });
        });

        document.querySelectorAll('[id$="-modal"]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        });
    });
</script>