@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard Ringkasan')

@section('content')

{{-- HEADER  --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="mb-1 text-xs font-bold uppercase tracking-widest text-secondary">Omni-channel Management</p>
        <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Dashboard</h1>
        <p class="mt-1.5 text-sm text-on-surface-variant">Kelola akun TikTok Shop &amp; produk Anda di satu tempat.</p>
    </div>
    <a href="{{ route('integrations.index') }}"
       class="inline-flex items-center gap-2 rounded-xl primary-gradient px-5 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
        <span class="material-symbols-outlined text-[18px]">add</span>
        Tambah Akun Marketplace
    </a>
</div>

{{-- STATS CARDS  --}}
<div class="mt-8 grid grid-cols-2 gap-5 sm:grid-cols-3 xl:grid-cols-6">

    {{-- Total Akun --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total Akun</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-primary">{{ $stats['total_accounts'] }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-fixed">
                <span class="material-symbols-outlined text-[16px] text-primary">people</span>
            </div>
            <p class="text-xs text-on-surface-variant">Marketplace terhubung</p>
        </div>
    </div>

    {{-- Akun Aktif --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Akun Aktif</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-secondary">{{ $stats['active_accounts'] }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-secondary-container">
                <span class="material-symbols-outlined text-[16px] text-on-secondary-container">check_circle</span>
            </div>
            <p class="text-xs text-on-surface-variant">Token valid</p>
        </div>
    </div>

    {{-- Total Produk --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total Produk</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_products']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-high">
                <span class="material-symbols-outlined text-[16px] text-primary">inventory_2</span>
            </div>
            <p class="text-xs text-on-surface-variant">SKU tersinkronisasi</p>
        </div>
    </div>

    {{-- TikTok --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Produk TikTok</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_tiktok']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background:#fe2c5510;">
                <svg class="h-4 w-4" fill="#fe2c55" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
            </div>
            <p class="text-xs text-on-surface-variant">Di TikTok Shop</p>
        </div>
    </div>

    {{-- Tokopedia --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Produk Tokopedia</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_tokopedia']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background:#03ac0e10;">
                <svg class="h-4 w-4" fill="#03ac0e" viewBox="0 0 24 24"><path d="M12 2C8.74 2 6.45 4.57 6.07 5.88H4.5a2 2 0 00-2 2v10.25a3.87 3.87 0 003.87 3.87h11.26a3.87 3.87 0 003.87-3.87V7.88a2 2 0 00-2-2h-1.57C17.55 4.57 15.26 2 12 2zm0 1.5c2.2 0 3.75 1.73 4.07 2.38H7.93C8.25 5.23 9.8 3.5 12 3.5zm0 5a3.5 3.5 0 110 7 3.5 3.5 0 010-7zm0 1.5a2 2 0 100 4 2 2 0 000-4z"/></svg>
            </div>
            <p class="text-xs text-on-surface-variant">Di Tokopedia</p>
        </div>
    </div>

    {{-- Shopee --}}
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest p-5 shadow-whisper">
        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Produk Shopee</p>
        <p class="mt-2 font-headline text-3xl font-extrabold text-on-surface">{{ number_format($stats['total_shopee']) }}</p>
        <div class="mt-3 flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background:#ee4d2d10;">
                <svg class="h-4 w-4" fill="#ee4d2d" viewBox="0 0 24 24"><path d="M12 2a5 5 0 00-5 5H5a2 2 0 00-2 2v11a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-2a5 5 0 00-5-5zm0 2a3 3 0 013 3H9a3 3 0 013-3zm-5 5h10v9H7V9z"/></svg>
            </div>
            <p class="text-xs text-on-surface-variant">Di Shopee</p>
        </div>
    </div>
</div>

{{-- STOCK SYNC WIDGET --}}
<div class="mt-8 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl primary-gradient text-white shadow-primary-glow">
                <span class="material-symbols-outlined text-[18px]">sync</span>
            </div>
            <div>
                <h2 class="font-headline text-sm font-bold text-on-surface">Sinkronisasi Stok</h2>
                <p class="text-xs text-on-surface-variant">Update stok otomatis ke TikTok &amp; Tokopedia</p>
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
        <div class="flex items-center gap-3 px-6 py-5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-secondary-container">
                <span class="material-symbols-outlined text-[18px] text-on-secondary-container">check_circle</span>
            </div>
            <div>
                <p class="font-headline text-xl font-bold text-on-surface">{{ number_format($syncStats['siap_sync']) }}</p>
                <p class="text-xs text-on-surface-variant">Produk Siap Sync</p>
            </div>
        </div>
        {{-- Jobs Pending --}}
        <div class="flex items-center gap-3 px-6 py-5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl
                        {{ $syncStats['jobs_pending'] > 0 ? 'bg-primary-fixed' : 'bg-surface-container' }}">
                <span class="material-symbols-outlined text-[18px] {{ $syncStats['jobs_pending'] > 0 ? 'text-primary' : 'text-on-surface-variant' }}">bolt</span>
            </div>
            <div>
                <p class="font-headline text-xl font-bold {{ $syncStats['jobs_pending'] > 0 ? 'text-primary' : 'text-on-surface' }}">
                    {{ number_format($syncStats['jobs_pending']) }}
                </p>
                <p class="text-xs text-on-surface-variant">Jobs di Queue</p>
            </div>
        </div>
        {{-- Last Sync --}}
        <div class="flex items-center gap-3 px-6 py-5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-surface-container-high">
                <span class="material-symbols-outlined text-[18px] text-primary">schedule</span>
            </div>
            <div>
                <p class="text-sm font-bold text-on-surface">
                    @if($syncStats['last_sync'])
                        {{ \Carbon\Carbon::parse($syncStats['last_sync'])->diffForHumans() }}
                    @else
                        <span class="text-tertiary-fixed-dim">Belum pernah</span>
                    @endif
                </p>
                <p class="text-xs text-on-surface-variant">Terakhir Sync</p>
            </div>
        </div>
    </div>
</div>

{{-- ACCOUNT LIST  --}}
<div class="mt-10">
    <h2 class="font-headline text-lg font-bold text-on-surface">Akun Terhubung</h2>
    <p class="mt-1 text-sm text-on-surface-variant">Daftar akun Marketplace yang sudah ditautkan.</p>

    @if($accounts->isEmpty())
        <div class="mt-6 flex flex-col items-center rounded-2xl border-2 border-dashed border-outline-variant/50 bg-surface-container-lowest p-12 text-center">
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
        <div class="mt-4 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($accounts as $account)
                <div class="group relative overflow-hidden rounded-2xl bg-surface-container-lowest p-6 shadow-whisper transition hover:shadow-md">

                    {{-- Status badge --}}
                    <div class="absolute right-4 top-4">
                        @if($account->status === 'active' && !$account->isTokenExpired())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container px-2.5 py-1 text-[10px] font-bold text-on-secondary-container">
                                <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Aktif
                            </span>
                        @elseif($account->status === 'active' && $account->isTokenExpired())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-tertiary-fixed px-2.5 py-1 text-[10px] font-bold text-on-tertiary-fixed-variant">
                                <span class="h-1.5 w-1.5 rounded-full bg-on-tertiary-container"></span> Token Expired
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-error-container px-2.5 py-1 text-[10px] font-bold text-on-error-container">
                                <span class="h-1.5 w-1.5 rounded-full bg-error"></span> {{ ucfirst($account->status) }}
                            </span>
                        @endif
                    </div>

                    {{-- Avatar + Info --}}
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $account->platform === 'SHOPEE' ? 'bg-orange-500' : 'primary-gradient' }} text-lg font-black text-white">
                            {{ strtoupper(substr($account->seller_name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate font-headline text-base font-bold text-on-surface">{{ $account->seller_name }}</h3>
                            <p class="text-xs text-on-surface-variant">
                                @if($account->platform === 'SHOPEE')
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-bold text-orange-700">Shopee</span>
                                @else
                                    {{ $account->seller_base_region ?? 'TikTok Shop' }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Stats strip --}}
                    <div class="mt-4 flex items-center gap-4 rounded-xl bg-surface-container-low px-4 py-3">
                        <div class="text-center">
                            <p class="font-headline text-lg font-bold text-on-surface">{{ number_format($account->produk_count) }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Total SKU</p>
                        </div>
                        <div class="h-8 w-px bg-outline-variant/30"></div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-on-surface">{{ $account->created_at->diffForHumans() }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Ditambahkan</p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 flex gap-2">
                        @if($account->platform === 'SHOPEE')
                            <form action="{{ route('shopee.sync-products', $account) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-orange-50 px-4 py-2.5 text-sm font-bold text-orange-600 transition hover:bg-orange-100">
                                    <span class="material-symbols-outlined text-[16px]">sync</span>
                                    Sync Produk
                                </button>
                            </form>
                            <form action="{{ route('shopee.disconnect', $account) }}" method="POST"
                                  onsubmit="return confirm('Putus koneksi {{ $account->seller_name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="flex items-center justify-center rounded-xl border border-error/20 bg-error-container/30 px-3 py-2.5 text-sm font-bold text-error transition hover:bg-error-container/60">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('tiktok.sync', $account) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-primary-fixed px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary-fixed-dim">
                                    <span class="material-symbols-outlined text-[16px]">sync</span>
                                    Sync Produk
                                </button>
                            </form>
                            <form action="{{ route('tiktok.destroy', $account) }}" method="POST"
                                  onsubmit="return confirm('Hapus akun {{ $account->seller_name }} beserta semua produknya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="flex items-center justify-center rounded-xl border border-error/20 bg-error-container/30 px-3 py-2.5 text-sm font-bold text-error transition hover:bg-error-container/60">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection
