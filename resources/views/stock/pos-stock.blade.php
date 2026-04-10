@extends('layouts.app')

@section('title', 'Cek Stok POS — ' . $account->seller_name)
@section('breadcrumb', 'Stok — Cek Stok POS')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock.dashboard') }}"
           class="inline-flex items-center justify-center rounded-xl bg-surface-container p-2 text-on-surface-variant transition hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline text-xl font-bold text-primary">Cek Stok POS</h1>
            <p class="mt-0.5 text-xs text-on-surface-variant">
                <span class="font-semibold text-on-surface">{{ $account->seller_name }}</span>
                <span class="mx-1 text-outline-variant">Â·</span>
                <span class="font-mono">ID Outlet: {{ $account->id_outlet }}</span>
                <span class="mx-1 text-outline-variant">Â·</span>
                {{ now()->format('d M Y, H:i') }} WIB
            </p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ url()->current() }}"
           class="inline-flex items-center gap-2 rounded-xl bg-surface-container px-4 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[18px]">refresh</span>
            Refresh
        </a>
        <a href="{{ route('stock.sync-account', $account->id) }}"
           onclick="return confirm('Dispatch job sync stok untuk akun ini?')"
           class="primary-gradient inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[18px]">cloud_sync</span>
            Sync ke Marketplace
        </a>
    </div>
</div>

{{-- SUMMARY CARDS --}}
<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    <div class="rounded-2xl bg-surface-container-lowest p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-on-surface">{{ number_format($summary['total_produk']) }}</p>
        <p class="mt-1 text-xs font-medium text-on-surface-variant">Total Produk</p>
    </div>
    <div class="rounded-2xl bg-primary-fixed p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-primary">{{ number_format($summary['total_tiktok']) }}</p>
        <p class="mt-1 text-xs font-medium text-primary/70">TikTok</p>
    </div>
    <div class="rounded-2xl bg-secondary-container/40 p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-secondary">{{ number_format($summary['total_tokopedia']) }}</p>
        <p class="mt-1 text-xs font-medium text-on-secondary-container/70">Tokopedia</p>
    </div>
    <div class="rounded-2xl bg-secondary-container/40 p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-secondary">{{ number_format($summary['siap_sync']) }}</p>
        <p class="mt-1 text-xs font-medium text-on-secondary-container/70">Siap Sync</p>
    </div>
    <div class="rounded-2xl bg-tertiary-fixed/60 p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-on-tertiary-fixed-variant">{{ number_format($summary['perlu_update']) }}</p>
        <p class="mt-1 text-xs font-medium text-on-tertiary-fixed-variant/70">Perlu Update</p>
    </div>
    <div class="rounded-2xl bg-error-container/30 p-4 text-center shadow-whisper">
        <p class="text-2xl font-bold text-error">{{ number_format($summary['sku_kosong']) }}</p>
        <p class="mt-1 text-xs font-medium text-error/70">SKU Kosong</p>
    </div>
</div>

{{-- TABS --}}
@if($hasilSiap->isEmpty() && $hasilSkuKosong->isEmpty())
    <div class="rounded-2xl bg-surface-container-lowest p-12 text-center shadow-whisper">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-surface-container">
            <span class="material-symbols-outlined text-[40px] text-on-surface-variant">inventory_2</span>
        </div>
        <h3 class="mt-4 text-base font-semibold text-on-surface">Belum ada produk</h3>
        <p class="mt-1 text-sm text-on-surface-variant">Lakukan sync produk dari TikTok Seller Center terlebih dahulu.</p>
    </div>
@else
<div x-data="{ tab: 'siap' }">

    {{-- Tab Headers --}}
    <div class="mb-0 flex gap-1 border-b border-outline-variant/30">
        <button @click="tab = 'siap'"
                :class="tab === 'siap' ? 'border-primary text-primary bg-surface-container-lowest' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container'"
                class="-mb-px inline-flex items-center gap-2 rounded-t-xl border-b-2 px-5 py-3 text-sm font-semibold transition">
            <span class="material-symbols-outlined text-[16px]">check_circle</span>
            Siap Sync
            <span :class="tab === 'siap' ? 'bg-primary-fixed text-primary' : 'bg-surface-container text-on-surface-variant'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilSiap->count() }}</span>
        </button>
        <button @click="tab = 'sku'"
                :class="tab === 'sku' ? 'border-primary text-primary bg-surface-container-lowest' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container'"
                class="-mb-px inline-flex items-center gap-2 rounded-t-xl border-b-2 px-5 py-3 text-sm font-semibold transition">
            <span class="material-symbols-outlined text-[16px]">warning</span>
            SKU Kosong
            <span :class="tab === 'sku' ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-surface-container text-on-surface-variant'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilSkuKosong->count() }}</span>
        </button>
        <button @click="tab = 'lewat'"
                :class="tab === 'lewat' ? 'border-primary text-primary bg-surface-container-lowest' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:bg-surface-container'"
                class="-mb-px inline-flex items-center gap-2 rounded-t-xl border-b-2 px-5 py-3 text-sm font-semibold transition">
            <span class="material-symbols-outlined text-[16px]">cancel</span>
            Dilewati
            <span :class="tab === 'lewat' ? 'bg-surface-container-high text-on-surface' : 'bg-surface-container text-on-surface-variant'"
                  class="rounded-full px-2 py-0.5 text-xs font-bold">{{ $hasilDilewati->count() }}</span>
        </button>
    </div>

    {{-- TAB: SIAP SYNC --}}
    <div x-show="tab === 'siap'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl bg-surface-container-lowest shadow-whisper">
            {{-- Search + Filter --}}
            <div class="flex flex-wrap items-center gap-3 border-b border-outline-variant/20 bg-surface-container-low px-5 py-3"
                 x-data="{ search: '', platform: '', only_update: false }">
                <div class="relative min-w-48 flex-1">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
                    <input x-model="search" type="text" placeholder="Cari nama produk atau SKU..."
                           class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2 pl-9 pr-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10">
                </div>
                <select x-model="platform"
                        class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10">
                    <option value="">Semua Platform</option>
                    <option value="TikTok">TikTok</option>
                    <option value="Tokopedia">Tokopedia</option>
                </select>
                <label class="flex cursor-pointer select-none items-center gap-2 rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container">
                    <input type="checkbox" x-model="only_update" class="h-4 w-4 rounded accent-primary">
                    Hanya yang perlu update
                </label>
            </div>
            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="tbl-siap">
                    <thead>
                        <tr class="border-b border-outline-variant/20 bg-surface-container-low text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
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
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($hasilSiap as $i => $p)
                            @php
                                $isOver     = $p['selisih'] > 0;
                                $isUnder    = $p['selisih'] < 0;
                                $selisihAbs = abs($p['selisih']);
                            @endphp
                            <tr class="transition hover:bg-surface-container-low"
                                x-data
                                :class="{
                                    'hidden': (search !== '' && !'{{ addslashes($p['title']) }}'.toLowerCase().includes(search.toLowerCase()) && !'{{ addslashes($p['seller_sku']) }}'.toLowerCase().includes(search.toLowerCase()))
                                          || (platform !== '' && '{{ $p['platform'] }}' !== platform)
                                          || (only_update && !{{ $p['perlu_update'] ? 'true' : 'false' }})
                                }">
                                <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-1 text-[11px] font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> TikTok
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-[11px] font-semibold text-on-secondary-container">
                                            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Tokopedia
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <p class="line-clamp-2 font-medium leading-snug text-on-surface">{{ $p['title'] }}</p>
                                    <p class="mt-0.5 truncate font-mono text-[10px] text-on-surface-variant">{{ $p['product_id'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded-lg bg-surface-container px-2 py-1 text-xs text-on-surface">{{ $p['seller_sku'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-bold text-on-surface">{{ number_format($p['stok_pos']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-base font-semibold text-on-surface-variant">{{ number_format($p['stok_mkt']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($p['selisih'] === 0)
                                        <span class="text-sm text-on-surface-variant">0</span>
                                    @elseif($isOver)
                                        <span class="inline-flex items-center gap-0.5 text-sm font-bold text-secondary">
                                            <span class="material-symbols-outlined text-[14px]">arrow_upward</span>
                                            +{{ number_format($p['selisih']) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-0.5 text-sm font-bold text-error">
                                            <span class="material-symbols-outlined text-[14px]">arrow_downward</span>
                                            {{ number_format($p['selisih']) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($p['perlu_update'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-1 text-[11px] font-semibold text-on-tertiary-fixed-variant">
                                            <span class="material-symbols-outlined text-[12px]">sync</span>
                                            Perlu Update
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-[11px] font-semibold text-on-secondary-container">
                                            <span class="material-symbols-outlined text-[12px]">check</span>
                                            Sama
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-on-surface-variant">
                                    Belum ada produk siap sync. Pastikan seller_sku sudah diisi di Seller Center.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: SKU KOSONG --}}
    <div x-show="tab === 'sku'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl bg-surface-container-lowest shadow-whisper">
            <div class="flex items-start gap-3 border-b border-outline-variant/20 bg-tertiary-fixed/50 px-5 py-4 text-sm text-on-tertiary-fixed-variant">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-[20px]">warning</span>
                <div>
                    <p class="font-semibold">Produk ini belum memiliki Seller SKU</p>
                    <p class="mt-0.5 opacity-80">Stok tidak bisa diambil dari POS. Isi <strong>Seller SKU</strong> di TikTok Seller Center (nomor yang sama dengan <code class="rounded bg-tertiary-fixed px-1">nomor_product</code> di POS), lalu lakukan <strong>Sync Produk</strong> ulang.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/20 bg-surface-container-low text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Product ID</th>
                            <th class="px-4 py-3">SKU ID</th>
                            <th class="px-4 py-3 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($hasilSkuKosong as $i => $p)
                            <tr class="transition hover:bg-tertiary-fixed/20">
                                <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-1 text-[11px] font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> TikTok
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-[11px] font-semibold text-on-secondary-container">
                                            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Tokopedia
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <p class="line-clamp-2 font-medium leading-snug text-on-surface">{{ $p['title'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="break-all font-mono text-xs text-on-surface-variant">{{ $p['product_id'] }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="break-all font-mono text-xs text-on-surface-variant">{{ $p['sku_id'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-1 text-[11px] font-medium text-on-tertiary-fixed-variant">
                                        Isi Seller SKU
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-on-surface-variant">
                                    ðŸŽ‰ Semua produk sudah memiliki Seller SKU!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB: DILEWATI --}}
    <div x-show="tab === 'lewat'" x-cloak>
        <div class="overflow-hidden rounded-b-2xl rounded-tr-2xl bg-surface-container-lowest shadow-whisper">
            <div class="flex items-center gap-2 border-b border-outline-variant/20 bg-surface-container-low px-5 py-3 text-xs text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px]">info</span>
                Produk dengan status DELETED, FREEZE, atau selain ACTIVATE tidak akan di-sync stoknya.
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/20 bg-surface-container-low text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Platform</th>
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">Seller SKU</th>
                            <th class="px-4 py-3 text-center">Status Produk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($hasilDilewati as $i => $p)
                            <tr class="opacity-70 transition hover:bg-surface-container-low hover:opacity-100">
                                <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($p['platform'] === 'TikTok')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-1 text-[11px] font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> TikTok
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-[11px] font-semibold text-on-secondary-container">
                                            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Tokopedia
                                        </span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-3">
                                    <p class="line-clamp-2 font-medium leading-snug text-on-surface-variant">{{ $p['title'] }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="rounded-lg bg-surface-container px-2 py-1 text-xs text-on-surface-variant">{{ $p['seller_sku'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full bg-surface-container px-2.5 py-1 text-[11px] font-semibold text-on-surface-variant">
                                        {{ $p['product_status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-on-surface-variant">
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
