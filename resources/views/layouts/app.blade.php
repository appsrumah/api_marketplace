<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    {{-- Prevent flash of unstyled content for dark mode --}}
    <script>
        (function() {
            const t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Kios Q</title>

    {{-- ── Google Fonts: Inter + Plus Jakarta Sans + Material Symbols ── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <style>
        /* Material Symbols variation settings */
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: inherit;
            line-height: 1;
            vertical-align: middle;
        }
        /* SweetAlert2 OmniCore overrides */
        .swal2-popup  { border-radius: 1rem !important; font-family: 'Inter', sans-serif !important; }
        .swal2-confirm { border-radius: 0.625rem !important; }
        /* Dark mode SweetAlert */
        .dark .swal2-popup  { background: #1e1c3c !important; color: #e4e1f6 !important; }
        .dark .swal2-title  { color: #e4e1f6 !important; }
        .dark .swal2-html-container { color: #c9c4d5 !important; }
    </style>
</head>
<body class="h-full bg-surface font-sans text-on-surface antialiased">

{{-- ══════════════════════════════════════════════════════════════════════
     OMNICORE LAYOUT: Sidebar (fixed left) + Top Header (fixed) + Main
     Mobile: sidebar hidden by default, toggled via hamburger
     ════════════════════════════════════════════════════════════════════ --}}

<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

{{-- ═══ MOBILE OVERLAY ════════════════════════════════════════════════ --}}
<div x-show="sidebarOpen" x-cloak
     @click="sidebarOpen = false"
     x-transition:enter="transition-opacity duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

{{-- ═══ SIDEBAR ════════════════════════════════════════════════════════ --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="sidebar-dark fixed left-0 top-0 z-50 flex h-screen w-64 flex-col border-r border-white/5 p-5 transition-transform duration-300 ease-in-out" style="background: #1e1449;">

    {{-- Brand + mobile close --}}
    <div class="mb-8 flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl primary-gradient text-sm font-black text-white shadow-primary-glow">
                KQ
            </div>
            <div class="leading-tight">
                <p class="text-base font-bold text-white font-headline">Kios Q</p>
                <p class="text-[10px] font-bold uppercase tracking-widest text-white/50">Omni-channel</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="flex lg:hidden h-8 w-8 items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition">
            <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-0.5 overflow-y-auto">

        <a href="{{ route('dashboard') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            Dashboard
        </a>

        <a href="{{ route('products.index') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('products.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">inventory_2</span>
            Produk Saya
        </a>

        <a href="{{ route('unified.orders.index') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('unified.orders.*') || request()->routeIs('orders.*') || request()->routeIs('shopee.orders.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
            Pesanan
        </a>

        <a href="{{ route('stock.dashboard') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('stock.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">sync</span>
            Sinkron Stok
        </a>

        <a href="{{ route('integrations.index') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('integrations.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">hub</span>
            Integrasi
        </a>

        @if(auth()->user()->canManageUsers())
        <a href="{{ route('users.index') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150
                  {{ request()->routeIs('users.*') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/10 hover:text-white' }}">
            <span class="material-symbols-outlined text-[20px]">group</span>
            Pengguna
        </a>
        @endif

    </nav>

    {{-- Bottom CTA --}}
    <div class="mt-4 border-t border-white/10 pt-4">
        <a href="{{ route('integrations.index') }}"
           class="flex w-full items-center justify-center gap-2 rounded-xl primary-gradient px-4 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Tambah Akun
        </a>
    </div>
</aside>

{{-- ═══ TOP HEADER ══════════════════════════════════════════════════════ --}}
<header class="fixed left-0 lg:left-64 right-0 top-0 z-40 flex h-16 items-center justify-between border-b border-outline-variant/20 px-4 sm:px-6 lg:px-8 glass-header shadow-whisper">

    {{-- Left: Hamburger (mobile) + Breadcrumb --}}
    <div class="flex items-center gap-3">
        {{-- Hamburger — only on mobile/tablet --}}
        <button @click="sidebarOpen = true"
                class="flex lg:hidden h-9 w-9 items-center justify-center rounded-xl border border-outline-variant/30 bg-surface-container-lowest text-on-surface-variant shadow-whisper transition hover:bg-surface-container-low hover:text-primary">
            <span class="material-symbols-outlined text-[20px]">menu</span>
        </button>
        <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant hidden sm:block">
            @yield('breadcrumb', 'Kios Q — Omni-channel Management')
        </p>
    </div>

    {{-- Right: Dark Mode Toggle + User Dropdown --}}
    <div class="flex items-center gap-2 sm:gap-3">

    {{-- Dark/Light Toggle --}}
    <button
        x-data="{ dark: document.documentElement.classList.contains('dark') }"
        @click="
            dark = !dark;
            document.documentElement.classList.add('transitioning');
            document.documentElement.classList.toggle('dark', dark);
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            setTimeout(() => document.documentElement.classList.remove('transitioning'), 350);
        "
        class="flex h-9 w-9 items-center justify-center rounded-xl border border-outline-variant/30 bg-surface-container-lowest text-on-surface-variant shadow-whisper transition hover:bg-surface-container-low hover:text-primary"
        :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
        <span x-show="!dark" class="material-symbols-outlined text-[20px]">dark_mode</span>
        <span x-show="dark" x-cloak class="material-symbols-outlined text-[20px]">light_mode</span>
    </button>

    <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
        <button @click="open = !open" @click.away="open = false"
                class="flex items-center gap-2 sm:gap-2.5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-2.5 sm:px-3 py-1.5 text-sm font-medium text-on-surface shadow-whisper transition hover:bg-surface-container-low">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg primary-gradient text-xs font-bold text-white">
                {{ auth()->user()->initials }}
            </div>
            <span class="hidden max-w-28 truncate sm:block font-semibold text-on-surface text-xs sm:text-sm">{{ auth()->user()->name }}</span>
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant transition" :class="open ? 'rotate-180' : ''">expand_more</span>
        </button>

        {{-- Dropdown --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
             class="absolute right-0 z-50 mt-2 w-60 sm:w-64 origin-top-right overflow-hidden rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-whisper">

            <div class="border-b border-outline-variant/20 bg-surface-container-low px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl primary-gradient text-sm font-bold text-white">
                        {{ auth()->user()->initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-on-surface font-headline">{{ auth()->user()->name }}</p>
                        <span class="inline-flex items-center rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold text-primary">
                            {{ auth()->user()->role_label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="py-1.5">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-on-surface-variant transition hover:bg-surface-container-low hover:text-primary">
                    <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                    Profil Saya
                </a>
                @if(auth()->user()->canManageUsers())
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-surface-container-low
                          {{ request()->routeIs('users.*') ? 'bg-primary-fixed text-primary font-semibold' : 'text-on-surface-variant hover:text-primary' }}">
                    <span class="material-symbols-outlined text-[18px]">group</span>
                    Manajemen Pengguna
                </a>
                @endif
            </div>

            <div class="border-t border-outline-variant/20 py-1.5">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-error transition hover:bg-error-container/30">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
    </div>
</header>

{{-- ═══ FLASH MESSAGES via SweetAlert ════════════════════════════════════ --}}
@if(session('success') || session('error') || session('warning') || session('info'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')),
        showConfirmButton: false, timer: 4000, timerProgressBar: true,
        customClass: { popup: 'rounded-2xl' } });
    @endif
    @if(session('error'))
    Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', html: @json(session('error')),
        confirmButtonColor: '#34179a', confirmButtonText: 'OK',
        customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-xl' } });
    @endif
    @if(session('warning'))
    Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: @json(session('warning')),
        showConfirmButton: false, timer: 5000, timerProgressBar: true,
        customClass: { popup: 'rounded-2xl' } });
    @endif
    @if(session('info'))
    Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: @json(session('info')),
        showConfirmButton: false, timer: 4000, timerProgressBar: true,
        customClass: { popup: 'rounded-2xl' } });
    @endif
});
</script>
@endif

{{-- ═══ MAIN CONTENT ════════════════════════════════════════════════════ --}}
<main class="ml-0 lg:ml-64 min-h-screen pt-16">
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @yield('content')
    </div>
</main>

</div>{{-- /x-data sidebarOpen --}}

@stack('scripts')
</body>
</html>
