<div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-gray-900">
            Purchase Request Detail
        </h2>

        <p class="text-sm text-gray-600">
            Simple sheet view for this purchase request
        </p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('purchasing.v2.requests.index') }}" class="inline-block bg-white text-gray-900 border border-gray-400 px-4 py-2 rounded text-sm hover:bg-gray-100">
            Back
        </a>

        <a href="#" class="inline-block bg-gray-900 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
            Print
        </a>
    </div>
</div>