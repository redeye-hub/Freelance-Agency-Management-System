<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} – Freelance Agency Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 via-white to-indigo-50 min-h-screen text-gray-800">
    <header class="max-w-6xl mx-auto px-6 py-5 flex items-center justify-between">
        <span class="text-xl font-bold text-indigo-600">FAMS</span>
        <nav class="flex items-center gap-4 text-sm">
            <a href="{{ route('inquiry.create') }}" class="text-gray-600 hover:text-indigo-600">Contact us</a>
            @auth
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600">Log in</a>
                <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Register</a>
            @endauth
        </nav>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-16 text-center">
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900">One place to run your <span class="text-indigo-600">freelance agency</span></h1>
        <p class="mt-5 text-lg text-gray-600 max-w-2xl mx-auto">Manage leads, clients and freelancers, and find the right freelancer by skill, rate and availability.</p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('inquiry.create') }}" class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Send an inquiry</a>
            <a href="{{ route('login') }}" class="px-6 py-3 rounded-xl bg-white border border-gray-200 text-gray-700 font-medium hover:bg-gray-50">Log in</a>
        </div>

        <div class="mt-16 grid sm:grid-cols-3 gap-6 text-left">
            <div class="rounded-2xl bg-sky-100 p-6"><h3 class="font-semibold text-sky-800">Leads &amp; Clients</h3><p class="mt-2 text-sm text-sky-900/80">Track every inquiry through a pipeline and convert won leads into clients.</p></div>
            <div class="rounded-2xl bg-emerald-100 p-6"><h3 class="font-semibold text-emerald-800">Skilled Freelancers</h3><p class="mt-2 text-sm text-emerald-900/80">Search by skill, hourly rate and availability.</p></div>
            <div class="rounded-2xl bg-amber-100 p-6"><h3 class="font-semibold text-amber-800">Role-based Access</h3><p class="mt-2 text-sm text-amber-900/80">Admin, manager, finance, freelancer and client each see only what they need.</p></div>
        </div>
    </main>
</body>
</html>