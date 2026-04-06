@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- ===== HEADER ===== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola akun TikTok Shop & produk Anda di satu tempat.</p>
    </div>
    <a href="{{ route('tiktok.connect') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-cyan-600 hover:to-blue-700 hover:shadow-blue-500/40 active:scale-[0.98]">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Akun TikTok
    </a>
</div>

{{-- ===== STATS CARDS ===== --}}
<div class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-5">
    {{-- Total Akun --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ $stats['total_accounts'] }}</p>
                <p class="text-xs font-medium text-slate-500">Total Akun</p>
            </div>
        </div>
    </div>

    {{-- Akun Aktif --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ $stats['active_accounts'] }}</p>
                <p class="text-xs font-medium text-slate-500">Akun Aktif</p>
            </div>
        </div>
    </div>

    {{-- Total Produk --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_products']) }}</p>
                <p class="text-xs font-medium text-slate-500">Total Produk</p>
            </div>
        </div>
    </div>

    {{-- TikTok --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_tiktok']) }}</p>
                <p class="text-xs font-medium text-slate-500">Produk TikTok</p>
            </div>
        </div>
    </div>

    {{-- Tokopedia --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_tokopedia']) }}</p>
                <p class="text-xs font-medium text-slate-500">Produk Tokopedia</p>
            </div>
        </div>
    </div>
</div>

{{-- ===== STOCK SYNC WIDGET ===== --}}
<div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-6 py-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-emerald-500 to-teal-600 text-white shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Sinkronisasi Stok</h2>
                <p class="text-xs text-slate-500">Update stok otomatis ke TikTok &amp; Tokopedia</p>
            </div>
        </div>
        <a href="{{ route('stock.dashboard') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Kelola Stok
        </a>
    </div>
    <div class="grid grid-cols-3 divide-x divide-slate-100 px-0 py-0">
        {{-- Siap Sync --}}
        <div class="flex items-center gap-3 px-6 py-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900">{{ number_format($syncStats['siap_sync']) }}</p>
                <p class="text-xs text-slate-500">Produk Siap Sync</p>
            </div>
        </div>
        {{-- Jobs Pending --}}
        <div class="flex items-center gap-3 px-6 py-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $syncStats['jobs_pending'] > 0 ? 'bg-blue-100' : 'bg-slate-100' }}">
                <svg class="h-4 w-4 {{ $syncStats['jobs_pending'] > 0 ? 'text-blue-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold {{ $syncStats['jobs_pending'] > 0 ? 'text-blue-700' : 'text-slate-900' }}">
                    {{ number_format($syncStats['jobs_pending']) }}
                </p>
                <p class="text-xs text-slate-500">Jobs di Queue</p>
            </div>
        </div>
        {{-- Last Sync --}}
        <div class="flex items-center gap-3 px-6 py-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-900">
                    @if($syncStats['last_sync'])
                        {{ \Carbon\Carbon::parse($syncStats['last_sync'])->diffForHumans() }}
                    @else
                        <span class="text-amber-500">Belum pernah</span>
                    @endif
                </p>
                <p class="text-xs text-slate-500">Terakhir Sync</p>
            </div>
        </div>
    </div>
</div>

{{-- ===== ACCOUNT LIST ===== --}}
<div class="mt-10">
    <h2 class="text-lg font-bold text-slate-900">Akun Terhubung</h2>
    <p class="mt-1 text-sm text-slate-500">Daftar akun TikTok Shop yang sudah ditautkan.</p>

    @if($accounts->isEmpty())
        <div class="mt-6 flex flex-col items-center rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada akun</h3>
            <p class="mt-1 text-sm text-slate-500">Hubungkan akun TikTok Shop Anda untuk memulai.</p>
            <a href="{{ route('tiktok.connect') }}"
               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Akun Pertama
            </a>
        </div>
    @else
        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($accounts as $account)
                <div class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                    {{-- Status badge --}}
                    <div class="absolute right-4 top-4">
                        @if($account->status === 'active' && !$account->isTokenExpired())
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                            </span>
                        @elseif($account->status === 'active' && $account->isTokenExpired())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Token Expired
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> {{ ucfirst($account->status) }}
                            </span>
                        @endif
                    </div>

                    {{-- Avatar + Info --}}
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-blue-600 text-lg font-bold text-white">
                            {{ strtoupper(substr($account->seller_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-slate-900">{{ $account->seller_name }}</h3>
                            <p class="text-xs text-slate-500">
                                {{ $account->seller_base_region }}
                                @if($account->shop_cipher)
                                    · <span class="font-mono text-[10px]">{{ Str::limit($account->shop_cipher, 20) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Product count --}}
                    <div class="mt-4 flex items-center gap-4 rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900">{{ number_format($account->produk_count) }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Total SKU</p>
                        </div>
                        <div class="h-8 w-px bg-slate-200"></div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-slate-900">{{ $account->created_at->diffForHumans() }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Ditambahkan</p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-4 flex gap-2">
                        <form action="{{ route('tiktok.sync', $account) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sync Produk
                            </button>
                        </form>
                        <form action="{{ route('tiktok.destroy', $account) }}" method="POST"
                              onsubmit="return confirm('Hapus akun {{ $account->seller_name }} beserta semua produknya?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex items-center justify-center rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection
