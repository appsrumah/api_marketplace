<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Kios Q TikTok Shop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">

    <div class="min-h-full">
        {{-- ===== NAVBAR ===== --}}
        <nav class="border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    {{-- Logo --}}
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-linear-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-md">
                            KQ
                        </div>
                        <span class="text-lg font-bold tracking-tight text-slate-800">
                            Kios Q <span class="font-medium text-slate-400">TikTok Shop</span>
                        </span>
                    </a>

                    {{-- Nav links --}}
                    <div class="flex items-center gap-1">
                        <a href="{{ route('dashboard') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            Dashboard
                        </a>
                        <a href="{{ route('products.index') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            Produk Saya
                        </a>
                        <a href="{{ route('stock.dashboard') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('stock.*') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sinkron Stok
                        </a>

                        {{-- ===== USER DROPDOWN ===== --}}
                        <div x-data="{ open: false }" class="relative ml-2" @keydown.escape="open = false">
                            <button @click="open = !open" @click.away="open = false"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
                                {{-- Avatar --}}
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white
                                    {{ auth()->user()->isSuperAdmin() ? 'bg-linear-to-br from-violet-500 to-purple-600' : (auth()->user()->isAdmin() ? 'bg-linear-to-br from-cyan-500 to-blue-600' : 'bg-linear-to-br from-slate-400 to-slate-500') }}">
                                    {{ auth()->user()->initials }}
                                </div>
                                <span class="hidden sm:block max-w-30 truncate">{{ auth()->user()->name }}</span>
                                <svg class="h-4 w-4 text-slate-400 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown panel --}}
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 z-50 mt-2 w-60 origin-top-right rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">

                                {{-- User info header --}}
                                <div class="border-b border-slate-100 px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white
                                            {{ auth()->user()->isSuperAdmin() ? 'bg-linear-to-br from-violet-500 to-purple-600' : (auth()->user()->isAdmin() ? 'bg-linear-to-br from-cyan-500 to-blue-600' : 'bg-linear-to-br from-slate-400 to-slate-500') }}">
                                            {{ auth()->user()->initials }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ auth()->user()->role_color }}">
                                                {{ auth()->user()->role_label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Menu items --}}
                                <div class="py-1.5">
                                    <a href="{{ route('profile.edit') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Profil Saya
                                    </a>

                                    @if(auth()->user()->canManageUsers())
                                    <a href="{{ route('users.index') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 {{ request()->routeIs('users.*') ? 'bg-violet-50 text-violet-700' : '' }}">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        Manajemen Pengguna
                                    </a>
                                    @endif
                                </div>

                                {{-- Logout --}}
                                <div class="border-t border-slate-100 py-1.5">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Keluar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- END USER DROPDOWN --}}
                    </div>
                </div>
            </div>
        </nav>

        {{-- ===== FLASH MESSAGES ===== --}}
        @if(session('success') || session('error') || session('warning'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <svg class="h-5 w-5 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ session('warning') }}
                </div>
            @endif
        </div>
        @endif

        {{-- ===== MAIN CONTENT ===== --}}
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
