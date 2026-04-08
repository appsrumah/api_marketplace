<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Kios Q TikTok Shop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        html, body, * { font-family: 'Plus Jakarta Sans', sans-serif; }
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
                        <a href="{{ route('orders.index') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('orders.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            Pesanan
                        </a>
                        <a href="{{ route('stock.dashboard') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('stock.*') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sinkron Stok
                        </a>
                        <a href="{{ route('integrations.index') }}"
                           class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('integrations.*') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800' }}">
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Integrasi
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

        {{-- ===== FLASH MESSAGES via SweetAlert ===== --}}
        @if(session('success') || session('error') || session('warning') || session('info'))
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: @json(session('success')),
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
            @endif
            @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                html: @json(session('error')),
                confirmButtonColor: '#3b82f6',
                confirmButtonText: 'OK',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' }
            });
            @endif
            @if(session('warning'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: @json(session('warning')),
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
            @endif
            @if(session('info'))
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: @json(session('info')),
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });
            @endif
        });
        </script>
        @endif

        {{-- ===== MAIN CONTENT ===== --}}
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
