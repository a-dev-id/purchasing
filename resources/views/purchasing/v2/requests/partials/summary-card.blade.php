<div class="bg-white border border-gray-300 p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">PR No</div>
            <div class="font-bold text-gray-900">
                {{ $purchaseRequest->request_number ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Date</div>
            <div>
                {{ optional($purchaseRequest->created_at)->format('d/m/Y') }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Department</div>
            <div>
                {{ $purchaseRequest->department_name ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Requester</div>
            <div>
                {{ $purchaseRequest->requester_name ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Request Name</div>
            <div class="font-semibold">
                {{ $purchaseRequest->title ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Priority</div>
            <div>
                {{ \Illuminate\Support\Str::of($purchaseRequest->priority ?? '-')->replace('_', ' ')->title() }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Status</div>

            @php
            $status = $purchaseRequest->status ?? '-';

            $statusClass = match ($status) {
            'draft' => 'border-gray-500 bg-gray-50 text-gray-700',
            'submitted' => 'border-blue-600 bg-blue-50 text-blue-700',

            'revision_to_purchasing_from_accounting',
            'revision_to_purchasing_from_gm' => 'border-orange-600 bg-orange-50 text-orange-700',

            'submitted_to_accounting',
            'submitted_to_gm' => 'border-yellow-600 bg-yellow-50 text-yellow-700',

            'approved',
            'gm_approved' => 'border-green-600 bg-green-50 text-green-700',

            'cancelled',
            'rejected' => 'border-red-600 bg-red-50 text-red-700',

            default => 'border-gray-400 bg-gray-50 text-gray-700',
            };
            @endphp

            <span class="inline-block border px-2 py-1 text-xs font-bold rounded {{ $statusClass }}">
                {{ \Illuminate\Support\Str::of($status)->replace('_', ' ')->title() }}
            </span>
        </div>

        <div>
            <div class="text-xs font-bold text-gray-500 mb-1">Last Update</div>
            <div>
                {{ optional($purchaseRequest->updated_at)->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    @if (! empty($purchaseRequest->request_notes))
    <div class="mt-4 border-t border-gray-200 pt-4">
        <div class="text-xs font-bold text-gray-500 mb-1">Remarks</div>

        <div class="prose prose-sm max-w-none">
            {!! nl2br(e(strip_tags($purchaseRequest->request_notes))) !!}
        </div>
    </div>
    @endif
</div>