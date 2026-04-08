@extends('layouts.app')

@section('title', 'Cek Stok POS — ' . $account->seller_name)

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock.dashboard') }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Cek Stok POS</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                <span class="font-semibold text-slate-700">{{ $account->seller_name }}</span>
                <span class="mx-1 text-slate-300">·</span>
                <span class="font-mono">ID Outlet: {{ $account->id_outlet }}</span>
                <span class="mx-1 text-slate-300">·</span>
                {{ now()->format('d M Y, H:i') }} WIB
            </p>
        </div>
    </div>
    <div class="flex gap-2">
        {{-- Reload --}}
        <a href="{{ url()->current() }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Refresh
        </a>
        {{-- Trigger Sync All --}}
        <a href="{{ route('tiktok.sync', $account->id) }}"
           onclick="return confirm('Dispatch job sync stok untuk akun ini?')"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Sync ke Marketplace
        </a>
    </div>
</div>

{{-- ===== SUMMARY CARDS ===== --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 mb-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-slate-900">{{ number_format($summary['total_produk']) }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Total Produk</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-rose-600">{{ number_format($summary['total_tiktok']) }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">TikTok</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ number_format($summary['total_tokopedia']) }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Tokopedia</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['siap_sync']) }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Siap Sync</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-amber-50 border-amber-200 p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-amber-600">{{ number_format($summary['perlu_update']) }}</p>
        <p class="mt-1 text-xs font-medium text-amber-600">Perlu Update</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-red-50 border-red-100 p-4 shadow-sm text-center">
        <p class="text-2xl font-bold text-red-500">{{ number_format($summary['sku_kosong']) }}</p>
        <p class="mt-1 text-xs font-medium text-red-500">SKU Kosong</p>
    </div>
</div>

{{-- ===== TABS ===== --}}
@if($hasilSiap->isEmpty() && $hasilSkuKosong->isEmpty())
    <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        </div>
        <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada produk</h3>
        <p class="mt-1 text-sm text-slate-500">Lakukan sync produk dari TikTok Seller Center terlebih dahulu.</p>
    </div>
@else
<div x-data="{ tab: 'siap' }">

    {{-- Tab Headers --}}
    <div class="flex gap-1 border-b border-slate-200 mb-0">
        <button @click="tab = 'siap'"
                :class="tab === 'siap' ? 'border-blue-600 text-blue-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                class="inline-flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition -mb-px rounded-t-xl">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Siap Sync
            <span :class="tab === 'siap' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilSiap->count() }}</span>
        </button>
        <button @click="tab = 'sku'"
                :class="tab === 'sku' ? 'border-amber-500 text-amber-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                class="inline-flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition -mb-px rounded-t-xl">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            SKU Kosong
            <span :class="tab === 'sku' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilSkuKosong->count() }}</span>
        </button>
        <button @click="tab = 'lewat'"
                :class="tab === 'lewat' ? 'border-slate-500 text-slate-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                class="inline-flex items-center gap-2 border-b-2 px-5 py-3 text-sm font-semibold transition -mb-px rounded-t-xl">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Dilewati
            <span :class="tab === 'lewat' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-500'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilDilewati->count() }}</span>
        </button>
    </div>

    {{-- ══════════════ TAB: SIAP SYNC ══════════════ --}}
    <div x-show="tab === 'siap'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl border border-t-0 border-slate-200 bg-white shadow-sm">
            {{-- Search + Filter --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3"
                 x-data="{ search: '', platform: '', only_update: false }">
                <div class="relative flex-1 min-w-48">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input x-model="search" type="text" placeholder="Cari nama produk atau SKU..."
                           class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-4 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
                <select x-model="platform"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Semua Platform</option>
                    <option value="TikTok">TikTok</option>
                    <option value="Tokopedia">Tokopedia</option>
                </select>
                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 select-none hover:bg-slate-50">
                    <input type="checkbox" x-model="only_update" class="h-4 w-4 rounded accent-amber-500">
                    Hanya yang perlu update
                </label>
            </div>
            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="tbl-siap">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Seller SKU</th>
                            <th class="px-4 py-3 text-center">Stok POS</th>
                            <th class="px-4 py-3 text-center">Stok Marketplace</th>
                            <th class="px-4 py-3 text-center">Selisih</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($hasilSiap as $i => $p)
                            @php
                                $isOver     = $p['selisih'] > 0;   // POS > Marketplace
                                $isUnder    = $p['selisih'] < 0;   // POS < Marketplace
                                $selisihAbs = abs($p['selisih']);
                            @endphp
                            <tr class="transition hover:bg-slate-50/60"
                                x-data
                                :class="{
                                    'hidden': (search !== '' && !'{{ addslashes($p['title']) }}'.toLowerCase().includes(search.toLowerCase()) && !'{{ addslashes($p['seller_sku']) }}'.toLowerCase().includes(search.toLowerCase()))
                                          || (platform !== '' && '{{ $p['platform'] }}' !== platform)
                                          || (only_update && !{{ $p['perlu_update'] ? 'true' : 'false' }})
                                }">
                                <td class="px-4 py-3 text-xs font-mono text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> TikTok</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tokopedia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <p class="font-medium text-slate-900 leading-snug line-clamp-2">{{ $p['title'] }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-slate-400 truncate">{{ $p['product_id'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $p['seller_sku'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-bold text-slate-900">{{ number_format($p['stok_pos']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-semibold text-slate-600">{{ number_format($p['stok_mkt']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($p['selisih'] === 0)
                                        <span class="text-slate-400 text-sm">—</span>
                                    @elseif($isOver)
                                        <span class="inline-flex items-center gap-0.5 text-sm font-bold text-blue-600">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            +{{ number_format($p['selisih']) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5 text-sm font-bold text-red-600">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            {{ number_format($p['selisih']) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($p['perlu_update'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Perlu Update
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-semibold text-green-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Sama
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-400">
                                    Belum ada produk siap sync. Pastikan seller_sku sudah diisi di Seller Center.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════ TAB: SKU KOSONG ══════════════ --}}
    <div x-show="tab === 'sku'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl border border-t-0 border-amber-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 bg-amber-50 px-5 py-4 text-sm text-amber-800 border-b border-amber-100">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="font-semibold">Produk ini belum memiliki Seller SKU</p>
                    <p class="mt-0.5 text-amber-700">Stok tidak bisa diambil dari POS. Isi <strong>Seller SKU</strong> di TikTok Seller Center (nomor yang sama dengan <code class="bg-amber-100 px-1 rounded">nomor_product</code> di POS), lalu lakukan <strong>Sync Produk</strong> ulang.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Product ID</th>
                            <th class="px-4 py-3">SKU ID</th>
                            <th class="px-4 py-3 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($hasilSkuKosong as $i => $p)
                            <tr class="transition hover:bg-amber-50/30">
                                <td class="px-4 py-3 text-xs font-mono text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> TikTok</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tokopedia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <p class="font-medium text-slate-900 line-clamp-2 leading-snug">{{ $p['title'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="text-xs font-mono text-slate-500 break-all">{{ $p['product_id'] }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="text-xs font-mono text-slate-500 break-all">{{ $p['sku_id'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-medium text-amber-700">
                                        Isi Seller SKU
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                                    🎉 Semua produk sudah memiliki Seller SKU!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════ TAB: DILEWATI ══════════════ --}}
    <div x-show="tab === 'lewat'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl border border-t-0 border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2 bg-slate-50 px-5 py-3 text-xs text-slate-500 border-b border-slate-100">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Produk dengan status DELETED, FREEZE, atau selain ACTIVATE tidak akan di-sync stoknya.
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Seller SKU</th>
                            <th class="px-4 py-3 text-center">Status Produk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($hasilDilewati as $i => $p)
                            <tr class="transition hover:bg-slate-50/40 opacity-70">
                                <td class="px-4 py-3 text-xs font-mono text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> TikTok</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tokopedia</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <p class="font-medium text-slate-600 line-clamp-2 leading-snug">{{ $p['title'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-500">{{ $p['seller_sku'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500">
                                        {{ $p['product_status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">
                                    Tidak ada produk yang dilewati.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endif

@endsection
