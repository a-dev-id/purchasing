<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Nandini Purchasing - Lite')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="min-h-screen bg-gray-100 text-gray-900 text-sm font-sans">

    @include('purchasing.v2.partials.navbar')

    <main class="p-4 md:p-6">
        @yield('content')
    </main>

    @stack('scripts')

</body>

</html>