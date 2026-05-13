<div class="bg-white border-b border-gray-300 px-4 md:px-6 py-3">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900">
                Nandini Purchasing - Lite
            </h1>

            <p class="text-xs md:text-sm text-gray-500">
                Simple yet advanced purchasing management system for older people!
            </p>
        </div>

        <div class="text-sm text-gray-700">
            {{ auth()->user()->name ?? 'User' }}
        </div>
    </div>
</div>

<div class="bg-gray-200 border-t border-b border-gray-300 px-4 py-3">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('purchasing.v2.dashboard') }}" class="px-4 py-2 border rounded text-sm
           {{ request()->routeIs('purchasing.v2.dashboard') || request()->routeIs('purchasing.v2.dashboard.index')
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            Dashboard
        </a>

        <a href="{{ route('purchasing.v2.need-my-action') }}" class="px-4 py-2 border rounded text-sm
                   {{ request()->routeIs('purchasing.v2.need-my-action')
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            Need My Action
        </a>

        <a href="#" class="px-4 py-2 border rounded text-sm
                   {{ request()->routeIs('purchasing.v2.my-requests')
                        ? 'bg-gray-900 text-white border-gray-900'
                        : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            My Requests
        </a>

        <a href="{{ route('purchasing.v2.items.index') }}" class="px-4 py-2 border rounded text-sm
           {{ request()->routeIs('purchasing.v2.items.*')
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            Item Master
        </a>

        <a href="#" class="px-4 py-2 border rounded text-sm
           {{ request()->routeIs('purchasing.v2.reports')
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            Reports
        </a>

        <a href="{{ route('purchasing.v2.requests.index') }}" class="px-4 py-2 border rounded text-sm md:ml-auto
           {{ request()->routeIs('purchasing.v2.requests.index')
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-900 border-gray-400 hover:bg-gray-100' }}">
            All Requests
        </a>
    </div>
</div>