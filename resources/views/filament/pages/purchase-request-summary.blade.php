<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">PR Summary</h2>
            <p class="mt-1 text-sm text-gray-600">
                Open the printable purchase request summary to view all PR in one place.
            </p>

            <div class="mt-4">
                <x-filament::button tag="a" :href="route('purchase-requests.summary-print')" target="_blank" rel="noopener noreferrer" color="warning" icon="heroicon-m-eye">
                    View the Summary
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>