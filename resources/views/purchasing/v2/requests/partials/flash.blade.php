@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-600 text-green-700 px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif

@if (session('error'))
<div class="mb-4 bg-red-50 border border-red-600 text-red-700 px-4 py-3 text-sm">
    {{ session('error') }}
</div>
@endif

@if ($errors->any())
<div class="mb-4 bg-red-50 border border-red-600 text-red-700 px-4 py-3 text-sm">
    <div class="font-bold mb-2">
        Please fix the following:
    </div>

    <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif