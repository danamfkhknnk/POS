<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Cashier' }} — POS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-100 antialiased">
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Cashier — {{ $outletName ?? auth()->user()?->outlet?->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()?->name }}</p>
                </div>
                <div class="hidden text-right sm:block">
                    <p class="text-xs text-gray-500">{{ now()->translatedFormat('l, d M Y') }}</p>
                </div>
            </div>
            <nav class="flex items-center gap-2">
                <a
                    href="{{ route('cashier.index') }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('cashier.index') ? 'bg-amber-100 text-amber-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    POS
                </a>
                <a
                    href="{{ route('cashier.transactions') }}"
                    class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('cashier.transactions') ? 'bg-amber-100 text-amber-700' : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    Transactions
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                        Sign out
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
