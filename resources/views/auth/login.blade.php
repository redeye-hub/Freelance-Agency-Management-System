{{-- resources/views/auth/login.blade.php (replace the whole file) --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ config('app.name', 'FAMS') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lg-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .lg-card { width: 100%; max-width: 380px; padding: 40px 32px 32px; border-radius: 28px; }
        .lg-cube-box { perspective: 700px; width: 56px; height: 56px; margin: 0 auto 22px; }
        .lg-cube { position: relative; width: 56px; height: 56px; transform-style: preserve-3d; animation: lg-spin 16s linear infinite; }
        .lg-cube span { position: absolute; inset: 0; border: 1px solid rgba(255, 255, 255, .9); border-radius: 6px;
            background: linear-gradient(135deg, rgba(255, 255, 255, .75), rgba(99, 102, 241, .35));
            box-shadow: inset 0 0 14px rgba(255, 255, 255, .6); }
        .lg-cube span:nth-child(1) { transform: translateZ(28px); }
        .lg-cube span:nth-child(2) { transform: rotateY(90deg) translateZ(28px); }
        .lg-cube span:nth-child(3) { transform: rotateY(180deg) translateZ(28px); }
        .lg-cube span:nth-child(4) { transform: rotateY(-90deg) translateZ(28px); }
        .lg-cube span:nth-child(5) { transform: rotateX(90deg) translateZ(28px); }
        .lg-cube span:nth-child(6) { transform: rotateX(-90deg) translateZ(28px); }
        @keyframes lg-spin { from { transform: rotateX(-24deg) rotateY(0deg); } to { transform: rotateX(-24deg) rotateY(360deg); } }
        @media (prefers-reduced-motion: reduce) { .lg-cube { animation: none; transform: rotateX(-24deg) rotateY(30deg); } }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased">
    <x-glass-bg />

    <main class="lg-wrap">
        <div class="bg-white lg-card">
            <div class="lg-cube-box" aria-hidden="true">
                <div class="lg-cube"><span></span><span></span><span></span><span></span><span></span><span></span></div>
            </div>

            <h1 class="text-center text-2xl font-bold tracking-tight text-gray-800">Sign in</h1>
            <p class="text-center text-sm text-gray-500 mt-1 mb-7">{{ config('app.name', 'FAMS') }}</p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label for="remember_me" class="inline-flex items-center gap-2 text-gray-600">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a class="text-indigo-600 hover:text-indigo-800" href="{{ route('password.request') }}">Forgot password?</a>
                    @endif
                </div>

                <x-primary-button class="w-full justify-center py-3">Sign in</x-primary-button>
            </form>

            @if (Route::has('register'))
                <p class="text-center text-sm text-gray-500 mt-6">
                    New here? <a class="text-indigo-600 hover:text-indigo-800 font-medium" href="{{ route('register') }}">Create an account</a>
                </p>
            @endif
        </div>
    </main>
</body>
</html>