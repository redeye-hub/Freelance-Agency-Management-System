<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Send an inquiry – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-sky-50 via-white to-indigo-50 min-h-screen text-gray-800">
    <div class="max-w-2xl mx-auto px-6 py-10">
        <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to home</a>
        <h1 class="mt-4 text-3xl font-bold text-gray-900">Tell us about your project</h1>
        <p class="mt-2 text-gray-600">Fill in the form and our team will get back to you.</p>

        @if (session('success'))
            <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('inquiry.store') }}" class="mt-6 bg-white rounded-2xl shadow-sm p-6 space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Your name *" />
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
                    <x-input-label for="company" value="Company" />
                    <x-text-input id="company" name="company" type="text" class="mt-1 block w-full" :value="old('company')" />
                    <x-input-error class="mt-1" :messages="$errors->get('company')" />
                </div>
            </div>
            <div>
                <x-input-label for="service" value="What do you need?" />
                <x-text-input id="service" name="service" type="text" class="mt-1 block w-full" placeholder="e.g. Website, mobile-friendly web app, logo design" :value="old('service')" />
                <x-input-error class="mt-1" :messages="$errors->get('service')" />
            </div>
            <div>
                <x-input-label for="message" value="Message *" />
                <textarea id="message" name="message" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('message')" />
            </div>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700">Send inquiry</button>
        </form>
    </div>
</body>
</html>