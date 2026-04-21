@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard Ringkasan')

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     HEADER
═══════════════════════════════════════════════════════════ --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-widest text-secondary">Omni-channel Management</p>
        <h1 class="font-headline text-2xl sm:text-3xl font-extrabold tracking-tight text-primary">Dashboard</h1>
        <p class="mt-1.5 text-sm text-on-surface-variant">Kelola semua akun marketplace &amp; produk Anda di satu tempat.</p>
    </div>
    <a href="{{ route('integrations.index') }}"
       class="inline-flex items-center gap-2 rounded-xl primary-gradient px-4 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98] self-start sm:self-auto">
        <span class="material-symbols-outlined text-[18px]">add</span>
        Tambah Akun
    </a>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STATS CARDS
═══════════════════════════════════════════════════════════ --}}
<div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 xl:grid-cols-6">

    {{-- Total Akun --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total Akun</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-primary">{{ $stats['total_accounts'] }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-fixed">
                <span class="material-symbols-outlined text-[16px] text-primary">people</span>
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">Marketplace terhubung</p>
        </div>
    </div>

    {{-- Akun Aktif --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Akun Aktif</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-secondary">{{ $stats['active_accounts'] }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-secondary-container">
                <span class="material-symbols-outlined text-[16px] text-on-secondary-container">check_circle</span>
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">Token valid</p>
        </div>
    </div>

    {{-- Total Produk --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px] col-span-2 sm:col-span-1">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total Produk</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_products']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-surface-container-high">
                <span class="material-symbols-outlined text-[16px] text-primary">inventory_2</span>
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">SKU tersinkronisasi</p>
        </div>
    </div>

    {{-- TikTok --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">TikTok</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_tiktok']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" style="background:#fe2c5510;">
                <img src="{{ asset('images/tiktok.svg') }}" alt="TikTok" class="h-4 w-4 object-contain" onerror="this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-[14px]\' style=\'color:#fe2c55\'>play_circle</span>'" />
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">TikTok Shop</p>
        </div>
    </div>

    {{-- Tokopedia --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tokopedia</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_tokopedia']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" style="background:#03ac0e10;">
                <img src="{{ asset('images/tokopedia.svg') }}" alt="Tokopedia" class="h-4 w-4 object-contain" onerror="this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-[14px]\' style=\'color:#03ac0e\'>store</span>'" />
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">Tokopedia</p>
        </div>
    </div>

    {{-- Shopee --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-4 sm:p-5 shadow-whisper flex flex-col justify-between min-h-[110px]">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Shopee</p>
        <p class="mt-2 font-headline text-2xl sm:text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_shopee']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" style="background:#ee4d2d10;">
                <img src="{{ asset('images/shopee.svg') }}" alt="Shopee" class="h-4 w-4 object-contain" onerror="this.parentElement.innerHTML='<span class=\'material-symbols-outlined text-[14px]\' style=\'color:#ee4d2d\'>shopping_bag</span>'" />
            </div>
            <p class="text-xs text-on-surface-variant leading-tight">Shopee</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     STOCK SYNC WIDGET
═══════════════════════════════════════════════════════════ --}}
<div class="mt-6 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/20 bg-surface-container-low px-4 sm:px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl primary-gradient text-white shadow-primary-glow">
                <span class="material-symbols-outlined text-[18px]">sync</span>
            </div>
            <div>
                <h2 class="font-headline text-sm font-bold text-on-surface">Sinkronisasi Stok</h2>
                <p class="text-xs text-on-surface-variant">Update stok otomatis ke semua marketplace</p>
            </div>
        </div>
        <a href="{{ route('stock.dashboard') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-secondary-container px-4 py-2 text-xs font-bold text-on-secondary-container transition hover:opacity-80">
            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
            Kelola Stok
        </a>
    </div>

    {{-- Stats strip --}}
    <div class="grid grid-cols-3 divide-x divide-outline-variant/20">
        {{-- Siap Sync --}}
        <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-6 py-4 sm:py-5">
            <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-secondary-container">
                <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-on-secondary-container">check_circle</span>
            </div>
            <div class="min-w-0">
                <p class="font-headline text-lg sm:text-xl font-bold text-on-surface">{{ number_format($syncStats['siap_sync']) }}</p>
                <p class="text-[10px] sm:text-xs text-on-surface-variant leading-tight">Produk Siap Sync</p>
            </div>
        </div>
        {{-- Jobs Pending --}}
        <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-6 py-4 sm:py-5">
            <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl
                        {{ $syncStats['jobs_pending'] > 0 ? 'bg-primary-fixed' : 'bg-surface-container' }}">
                <span class="material-symbols-outlined text-[16px] sm:text-[18px] {{ $syncStats['jobs_pending'] > 0 ? 'text-primary' : 'text-on-surface-variant' }}">bolt</span>
            </div>
            <div class="min-w-0">
                <p class="font-headline text-lg sm:text-xl font-bold {{ $syncStats['jobs_pending'] > 0 ? 'text-primary' : 'text-on-surface' }}">
                    {{ number_format($syncStats['jobs_pending']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-on-surface-variant leading-tight">Jobs di Queue</p>
            </div>
        </div>
        {{-- Last Sync --}}
        <div class="flex items-center gap-2 sm:gap-3 px-3 sm:px-6 py-4 sm:py-5">
            <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-xl bg-surface-container-high">
                <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-primary">schedule</span>
            </div>
            <div class="min-w-0">
                <p class="text-xs sm:text-sm font-bold text-on-surface truncate">
                    @if($syncStats['last_sync'])
                        {{ \Carbon\Carbon::parse($syncStats['last_sync'])->diffForHumans() }}
                    @else
                        <span class="text-on-surface-variant font-normal">Belum pernah</span>
                    @endif
                </p>
                <p class="text-[10px] sm:text-xs text-on-surface-variant leading-tight">Terakhir Sync</p>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     ACCOUNT LIST — with platform filter tabs
═══════════════════════════════════════════════════════════ --}}
<div class="mt-8" x-data="accountFilter()">

    {{-- Section header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-headline text-lg font-bold text-on-surface">Akun Terhubung</h2>
            <p class="mt-0.5 text-sm text-on-surface-variant">
                Semua akun marketplace aktif &amp; non-aktif.
                @if(auth()->user()?->isSuperAdmin())
                    <span class="ml-1 text-xs font-semibold text-tertiary">👑 Super Admin</span>
                @endif
            </p>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex items-center gap-1.5 rounded-xl bg-surface-container-low p-1 self-start sm:self-auto">
            <button @click="filter = 'all'"
                    :class="filter === 'all' ? 'bg-surface-container-lowest shadow-sm text-primary font-bold' : 'text-on-surface-variant hover:text-on-surface'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150">
                <span class="material-symbols-outlined text-[14px]">apps</span>
                Semua
                <span class="rounded-full bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary leading-none">{{ $accounts->count() }}</span>
            </button>
            <button @click="filter = 'tiktok'"
                    :class="filter === 'tiktok' ? 'bg-surface-container-lowest shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                    :style="filter === 'tiktok' ? 'color:#fe2c55' : ''">
                <span class="material-symbols-outlined text-[14px]">play_circle</span>
                TikTok
                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none"
                      :style="filter === 'tiktok' ? 'background:#fe2c5515;color:#fe2c55' : 'background:rgba(0,0,0,0.06);color:inherit'">
                    {{ $accounts->where('platform', 'TIKTOK')->count() }}
                </span>
            </button>
            <button @click="filter = 'shopee'"
                    :class="filter === 'shopee' ? 'bg-surface-container-lowest shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                    :style="filter === 'shopee' ? 'color:#ee4d2d' : ''">
                <span class="material-symbols-outlined text-[14px]">shopping_bag</span>
                Shopee
                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none"
                      :style="filter === 'shopee' ? 'background:#ee4d2d15;color:#ee4d2d' : 'background:rgba(0,0,0,0.06);color:inherit'">
                    {{ $accounts->where('platform', 'SHOPEE')->count() }}
                </span>
            </button>
        </div>
    </div>

    @if($accounts->isEmpty())
        <div class="mt-6 flex flex-col items-center rounded-2xl border-2 border-dashed border-outline-variant/50 bg-surface-container-lowest p-10 sm:p-14 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-fixed">
                <span class="material-symbols-outlined text-[32px] text-primary">person_add</span>
            </div>
            <h3 class="mt-4 font-headline text-base font-bold text-on-surface">Belum ada akun</h3>
            <p class="mt-1 text-sm text-on-surface-variant">Hubungkan akun Marketplace Anda untuk memulai.</p>
            <a href="{{ route('integrations.index') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl primary-gradient px-5 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Tambah Akun Pertama
            </a>
        </div>
    @else
        {{-- Account Grid --}}
        <div class="mt-4 grid gap-4 sm:gap-5 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($accounts as $account)
                {{-- Determine if visible --}}
                @php
                    $platformKey = strtolower($account->platform === 'SHOPEE' ? 'shopee' : 'tiktok');
                    $isActive   = $account->status === 'active';
                    $tokenOk    = $isActive && !$account->isTokenExpired();
                    $displayName = $account->platform === 'SHOPEE'
                        ? ($account->seller_name ?? 'Shopee Shop')
                        : ($account->shop_name ?: $account->seller_name ?? 'TikTok Shop');
                @endphp

                <div class="group relative overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 border border-outline-variant/20"
                     x-show="filter === 'all' || filter === '{{ $platformKey }}'"
                     x-transition:enter="transition duration-150 ease-out"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">

                    {{-- Top accent line by platform --}}
                    <div class="h-0.5 w-full {{ $account->platform === 'SHOPEE' ? '' : 'primary-gradient' }}"
                         @if($account->platform === 'SHOPEE') style="background:linear-gradient(90deg,#ee4d2d,#ff7337)" @endif></div>

                    <div class="p-4 sm:p-5">

                        {{-- Header row: Avatar + Name + Status --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Avatar --}}
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base font-black text-white shadow-sm
                                            {{ $account->platform === 'SHOPEE' ? '' : 'primary-gradient' }}"
                                     @if($account->platform === 'SHOPEE') style="background:linear-gradient(135deg,#ee4d2d,#ff7337)" @endif>
                                    {{ strtoupper(mb_substr($displayName, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate font-headline text-sm font-bold text-on-surface leading-tight">{{ $displayName }}</h3>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        {{-- Platform badge --}}
                                        @if($account->platform === 'SHOPEE')
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold" style="background:#ee4d2d15;color:#ee4d2d">
                                                <span class="material-symbols-outlined text-[10px]">shopping_bag</span>Shopee
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold" style="background:#fe2c5515;color:#fe2c55">
                                                <span class="material-symbols-outlined text-[10px]">play_circle</span>TikTok
                                            </span>
                                        @endif
                                        {{-- Region (TikTok only) --}}
                                        @if($account->platform !== 'SHOPEE' && $account->seller_base_region)
                                            <span class="text-[10px] text-on-surface-variant">{{ $account->seller_base_region }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Status badge --}}
                            @if($tokenOk)
                                <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-1 text-[10px] font-bold text-on-secondary-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Aktif
                                </span>
                            @elseif($isActive && $account->isTokenExpired())
                                <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Expired
                                </span>
                            @else
                                <span class="shrink-0 inline-flex items-center gap-1 rounded-full bg-error-container px-2 py-1 text-[10px] font-bold text-on-error-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-error"></span> {{ ucfirst($account->status) }}
                                </span>
                            @endif
                        </div>

                        {{-- Stats bar --}}
                        <div class="mt-4 flex items-center gap-3 rounded-xl bg-surface-container-low px-4 py-3">
                            <div class="flex-1 text-center">
                                <p class="font-headline text-lg font-bold text-on-surface leading-none">{{ number_format($account->produk_count) }}</p>
                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Produk</p>
                            </div>
                            <div class="h-8 w-px bg-outline-variant/30"></div>
                            <div class="flex-1 text-center">
                                <p class="text-xs font-bold text-on-surface leading-none">
                                    @if($account->last_sync_at)
                                        {{ $account->last_sync_at->diffForHumans(null, true) }}
                                    @else
                                        <span class="font-normal text-on-surface-variant text-[10px]">Belum sync</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Sync Terakhir</p>
                            </div>
                            <div class="h-8 w-px bg-outline-variant/30"></div>
                            <div class="flex-1 text-center">
                                <p class="text-xs font-bold text-on-surface leading-none">{{ $account->created_at->format('d M y') }}</p>
                                <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Ditambahkan</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="mt-3 flex gap-2">
                            @if($account->platform === 'SHOPEE')
                                {{-- Sync Produk Shopee --}}
                                <form action="{{ route('shopee.sync-products', $account) }}" method="POST" class="flex-1"
                                      x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button type="submit" :disabled="loading"
                                            class="flex w-full items-center justify-center gap-1.5 rounded-xl px-3 py-2.5 text-xs font-bold transition
                                                   disabled:opacity-60 disabled:cursor-not-allowed"
                                            style="background:#ee4d2d15;color:#ee4d2d"
                                            onmouseover="this.style.background='#ee4d2d25'" onmouseout="this.style.background='#ee4d2d15'">
                                        <span class="material-symbols-outlined text-[15px]" x-show="!loading">sync</span>
                                        <span class="material-symbols-outlined text-[15px] animate-spin" x-show="loading" x-cloak>progress_activity</span>
                                        <span x-text="loading ? 'Memproses...' : 'Sync Produk'"></span>
                                    </button>
                                </form>
                                {{-- Disconnect --}}
                                <form action="{{ route('shopee.disconnect', $account) }}" method="POST"
                                      x-data onsubmit="return confirm('Putus koneksi akun {{ addslashes($displayName) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="flex items-center justify-center rounded-xl border border-error/20 bg-error-container/30 px-3 py-2.5 text-error transition hover:bg-error-container/60">
                                        <span class="material-symbols-outlined text-[16px]">link_off</span>
                                    </button>
                                </form>
                            @else
                                {{-- Sync Produk TikTok --}}
                                <form action="{{ route('tiktok.sync', $account) }}" method="POST" class="flex-1"
                                      x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    <button type="submit" :disabled="loading"
                                            class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-primary-fixed px-3 py-2.5 text-xs font-bold text-primary transition hover:bg-primary-fixed-dim disabled:opacity-60 disabled:cursor-not-allowed">
                                        <span class="material-symbols-outlined text-[15px]" x-show="!loading">sync</span>
                                        <span class="material-symbols-outlined text-[15px] animate-spin" x-show="loading" x-cloak>progress_activity</span>
                                        <span x-text="loading ? 'Memproses...' : 'Sync Produk'"></span>
                                    </button>
                                </form>
                                {{-- Delete --}}
                                <form action="{{ route('tiktok.destroy', $account) }}" method="POST"
                                      x-data onsubmit="return confirm('Hapus akun {{ addslashes($displayName) }} beserta semua produknya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="flex items-center justify-center rounded-xl border border-error/20 bg-error-container/30 px-3 py-2.5 text-error transition hover:bg-error-container/60">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>{{-- /p-5 --}}
                </div>{{-- /card --}}
            @endforeach
        </div>{{-- /grid --}}

        {{-- Empty state when filter returns nothing --}}
        <div x-show="visibleCount === 0" x-cloak class="mt-6 rounded-2xl border-2 border-dashed border-outline-variant/50 p-10 text-center">
            <p class="text-sm text-on-surface-variant">Tidak ada akun untuk platform yang dipilih.</p>
        </div>
    @endif
</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function accountFilter() {
    return {
        filter: 'all',
        get visibleCount() {
            if (this.filter === 'all') return {{ $accounts->count() }};
            if (this.filter === 'tiktok') return {{ $accounts->where('platform', 'TIKTOK')->count() }};
            if (this.filter === 'shopee') return {{ $accounts->where('platform', 'SHOPEE')->count() }};
            return 0;
        }
    };
}
</script>
@endpush
