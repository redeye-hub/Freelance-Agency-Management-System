<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join as a freelancer – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 via-white to-indigo-50 min-h-screen text-gray-800">
    <div class="max-w-2xl mx-auto px-6 py-10">
        <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to home</a>
        <h1 class="mt-4 text-3xl font-bold text-gray-900">Join our freelancer network</h1>
        <p class="mt-2 text-gray-600">Tell us about your skills. Our HR team reviews every application.</p>

        @if (session('success'))
            <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('apply.store') }}" class="mt-6 bg-white rounded-2xl shadow-sm p-6 space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Full name *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="email" value="Email *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    <x-input-error class="mt-1" :messages="$errors->get('email')" />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                    <x-input-error class="mt-1" :messages="$errors->get('phone')" />
                </div>
                <div>
                    <x-input-label for="expected_rate" value="Expected hourly rate (USD)" />
                    <x-text-input id="expected_rate" name="expected_rate" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('expected_rate')" />
                    <x-input-error class="mt-1" :messages="$errors->get('expected_rate')" />
                </div>
            </div>
            <div>
                <x-input-label for="headline" value="Headline" />
                <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full" placeholder="e.g. Full-stack Laravel developer" :value="old('headline')" />
                <x-input-error class="mt-1" :messages="$errors->get('headline')" />
            </div>
            <div>
                <x-input-label for="skills" value="Skills (comma separated)" />
                <x-text-input id="skills" name="skills" type="text" class="mt-1 block w-full" placeholder="Laravel, Vue, MySQL" :value="old('skills')" />
                <x-input-error class="mt-1" :messages="$errors->get('skills')" />
            </div>
            <div>
                <x-input-label for="portfolio_url" value="Portfolio link" />
                <x-text-input id="portfolio_url" name="portfolio_url" type="url" class="mt-1 block w-full" placeholder="https://" :value="old('portfolio_url')" />
                <x-input-error class="mt-1" :messages="$errors->get('portfolio_url')" />
            </div>
            <div>
                <x-input-label for="cover_note" value="Why do you want to join? *" />
                <textarea id="cover_note" name="cover_note" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('cover_note') }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('cover_note')" />
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Submit application</button>
        </form>
    </div>
</body>
</html>
