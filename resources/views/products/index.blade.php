@extends('layouts.app')
@section('title', 'Produk Saya')
@section('breadcrumb', 'Produk — Manajemen Katalog')

@section('content')
<div class="space-y-6">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-secondary">Marketplace</p>
            <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Produk Saya</h1>
            <p class="mt-1.5 text-sm text-on-surface-variant">Kelola semua produk dari akun marketplace yang terhubung.</p>
        </div>
    </div>

    {{-- ===== STATS BAR ===== --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface">
            Total: {{ number_format($stats['total']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1.5 text-xs font-semibold text-primary">
            <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> TikTok: {{ number_format($stats['tiktok']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Tokopedia: {{ number_format($stats['tokopedia']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container/50 px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Aktif: {{ number_format($stats['active']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-tertiary-fixed px-3 py-1.5 text-xs font-semibold text-on-tertiary-fixed-variant">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Nonaktif: {{ number_format($stats['deactivated']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1.5 text-xs font-semibold text-on-surface-variant">
            <span class="h-1.5 w-1.5 rounded-full bg-on-surface-variant/50"></span> Draft: {{ number_format($stats['draft']) }}
        </span>
    </div>

    {{-- ===== FILTER BAR ===== --}}
    <form method="GET" action="{{ route('products.index') }}">
        <div class="flex flex-wrap items-end gap-3 rounded-2xl bg-surface-container-lowest p-4 shadow-whisper">
            {{-- Search --}}
            <div class="min-w-50 flex-1">
                <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Cari Produk</label>
                <div class="relative">
                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant/50">search</span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama produk, SKU, atau ID..."
                           class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2.5 pl-10 pr-4 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                </div>
            </div>

            {{-- Akun --}}
            <div class="w-44">
                <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Akun / Toko</label>
                <select name="account_id"
                        class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                    <option value="">Semua Akun</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->shop_name ?? $acc->seller_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Platform --}}
            <div class="w-36">
                <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Platform</label>
                <select name="platform"
                        class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                    <option value="">Semua</option>
                    <option value="TIKTOK" {{ request('platform') === 'TIKTOK' ? 'selected' : '' }}>TikTok</option>
                    <option value="TOKOPEDIA" {{ request('platform') === 'TOKOPEDIA' ? 'selected' : '' }}>Tokopedia</option>
                </select>
            </div>

            {{-- Status --}}
            <div class="w-48">
                <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Status</label>
                <select name="status"
                        class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                    <option value="ALL"  {{ request('status', 'ALL') === 'ALL'  ? 'selected' : '' }}>Semua Status</option>
                    <option value="ACTIVATE"             {{ request('status') === 'ACTIVATE'             ? 'selected' : '' }}>✅ Aktif</option>
                    <option value="DRAFT"                {{ request('status') === 'DRAFT'                ? 'selected' : '' }}>📝 Draft</option>
                    <option value="PENDING"              {{ request('status') === 'PENDING'              ? 'selected' : '' }}>⏳ Pending</option>
                    <option value="FAILED"               {{ request('status') === 'FAILED'               ? 'selected' : '' }}>❌ Gagal</option>
                    <option value="SELLER_DEACTIVATED"   {{ request('status') === 'SELLER_DEACTIVATED'   ? 'selected' : '' }}>🔕 Nonaktif (Seller)</option>
                    <option value="PLATFORM_DEACTIVATED" {{ request('status') === 'PLATFORM_DEACTIVATED' ? 'selected' : '' }}>🚫 Nonaktif (Platform)</option>
                    <option value="FREEZE"               {{ request('status') === 'FREEZE'               ? 'selected' : '' }}>❄️ Frozen</option>
                    <option value="DELETED"              {{ request('status') === 'DELETED'              ? 'selected' : '' }}>🗑️ Dihapus</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="primary-gradient inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span>
                    Filter
                </button>
                @if(request()->hasAny(['search', 'platform', 'status', 'account_id']))
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ===== PRODUCTS TABLE ===== --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        @if($products->isEmpty())
            <div class="flex flex-col items-center p-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-fixed">
                    <span class="material-symbols-outlined text-[32px] text-primary">inventory_2</span>
                </div>
                <h3 class="mt-4 text-base font-semibold text-on-surface">Belum ada produk</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Tambahkan akun marketplace dan sync produk untuk melihat data di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/20 bg-surface-container-low">
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">#</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Produk</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Platform</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">SKU POS</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-on-surface-variant">Harga</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Stok</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @foreach($products as $product)
                        @php
                            $detail   = $product->detail;
                            $imgUrl   = null;
                            if ($detail && !empty($detail->main_images)) {
                                $imgs = is_array($detail->main_images) ? $detail->main_images : json_decode($detail->main_images, true);
                                $firstImg = $imgs[0] ?? null;
                                if (is_array($firstImg)) {
                                    $imgUrl = $firstImg['urls'][0] ?? $firstImg['url'] ?? $firstImg['thumb_url'] ?? null;
                                } elseif (is_string($firstImg)) {
                                    $imgUrl = $firstImg;
                                }
                            }
                            $statusConfig = [
                                'ACTIVATE'             => ['color' => 'bg-secondary-container text-on-secondary-container',      'label' => 'Aktif'],
                                'DRAFT'                => ['color' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant',        'label' => 'Draft'],
                                'PENDING'              => ['color' => 'bg-primary-fixed text-primary',                          'label' => 'Pending'],
                                'FAILED'               => ['color' => 'bg-error-container text-on-error-container',              'label' => 'Gagal'],
                                'SELLER_DEACTIVATED'   => ['color' => 'bg-surface-container-high text-on-surface-variant',      'label' => 'Nonaktif'],
                                'PLATFORM_DEACTIVATED' => ['color' => 'bg-error-container/50 text-on-error-container',          'label' => 'Diblokir'],
                                'FREEZE'               => ['color' => 'bg-surface-container text-on-surface-variant',           'label' => 'Frozen'],
                                'DELETED'              => ['color' => 'bg-error-container/30 text-on-error-container',          'label' => 'Dihapus'],
                            ];
                            $cfg = $statusConfig[$product->product_status] ?? ['color' => 'bg-surface-container text-on-surface', 'label' => $product->product_status];
                        @endphp
                        <tr class="transition hover:bg-surface-container-low">
                            {{-- # --}}
                            <td class="px-4 py-3 align-top font-mono text-xs text-on-surface-variant">
                                {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                            </td>

                            {{-- Produk (with image) --}}
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    {{-- Thumbnail --}}
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-primary-fixed">
                                        @if($imgUrl)
                                            <img src="{{ $imgUrl }}" alt="{{ $product->title }}" class="h-full w-full object-cover" loading="lazy"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <div class="hidden h-full w-full items-center justify-center">
                                                <span class="material-symbols-outlined text-[20px] text-primary">image</span>
                                            </div>
                                        @else
                                            <div class="flex h-full w-full items-center justify-center">
                                                <span class="material-symbols-outlined text-[20px] text-primary">image</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('products.detail', $product->product_id) }}"
                                           class="block max-w-xs truncate font-medium text-on-surface hover:text-primary hover:underline"
                                           title="{{ $product->title }}">
                                            {{ $product->title }}
                                        </a>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px] text-on-surface-variant">
                                            @if($product->account)
                                                <span class="text-primary">{{ $product->account->shop_name ?? $product->account->seller_name }}</span>
                                                <span>•</span>
                                            @endif
                                            <span class="font-mono">ID: {{ $product->product_id ?: '-' }}</span>
                                            <span>•</span>
                                            <span class="font-mono">SKU: {{ $product->sku_id ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Platform --}}
                            <td class="px-4 py-3 align-top">
                                @if($product->platform === 'TIKTOK')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-1 text-xs font-semibold text-primary">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
                                        TikTok
                                    </span>
                                @elseif($product->platform === 'TOKOPEDIA')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-xs font-semibold text-on-secondary-container">
                                        Tokopedia
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-surface-container px-2.5 py-1 text-xs font-semibold text-on-surface">
                                        {{ $product->platform }}
                                    </span>
                                @endif
                            </td>

                            {{-- SKU POS --}}
                            <td class="px-4 py-3 align-top">
                                @if($product->seller_sku)
                                    <span class="inline-flex items-center rounded-lg bg-surface-container px-2 py-1 font-mono text-xs text-on-surface">
                                        {{ $product->seller_sku }}
                                    </span>
                                @else
                                    <span class="text-xs text-on-surface-variant/40">—</span>
                                @endif
                            </td>

                            {{-- Harga --}}
                            <td class="px-4 py-3 text-right align-top">
                                <span class="font-semibold text-on-surface">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            </td>

                            {{-- Stok --}}
                            <td class="px-4 py-3 text-center align-top">
                                @if($product->quantity > 0)
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-secondary-container/50 px-2 py-1 text-xs font-bold text-on-secondary-container">
                                        {{ $product->quantity }}
                                    </span>
                                @else
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-error-container/40 px-2 py-1 text-xs font-bold text-on-error-container">
                                        0
                                    </span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $cfg['color'] }}"
                                      title="{{ $product->product_status }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-4 py-3 text-center align-top">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Detail / Sinkron --}}
                                    <a href="{{ route('products.detail', ['productId' => $product->product_id, 'account_id' => $product->account_id, 'refresh' => $detail ? null : 1]) }}"
                                       class="inline-flex items-center justify-center rounded-lg bg-primary-fixed p-1.5 text-primary transition hover:bg-primary-fixed/70"
                                       title="{{ $detail ? 'Lihat Detail' : 'Ambil Detail dari API' }}">
                                        <span class="material-symbols-outlined text-[16px]">{{ $detail ? 'visibility' : 'sync' }}</span>
                                    </a>

                                    {{-- Edit --}}
                                    @if($detail)
                                    <a href="{{ route('products.edit', $product->product_id) }}"
                                       class="inline-flex items-center justify-center rounded-lg bg-tertiary-fixed p-1.5 text-on-tertiary-fixed-variant transition hover:opacity-80"
                                       title="Edit Produk">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </a>
                                    @endif

                                    {{-- Sinkron Ulang Detail --}}
                                    @if($detail)
                                    <a href="{{ route('products.detail', ['productId' => $product->product_id, 'account_id' => $product->account_id, 'refresh' => 1]) }}"
                                       class="inline-flex items-center justify-center rounded-lg bg-secondary-container/40 p-1.5 text-secondary transition hover:bg-secondary-container"
                                       title="Sinkron Ulang Detail dari API">
                                        <span class="material-symbols-outlined text-[16px]">refresh</span>
                                    </a>
                                    @endif

                                    {{-- Hapus --}}
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                                          x-data
                                          @submit.prevent="Swal.fire({
                                              title: 'Hapus Produk?',
                                              text: '{{ addslashes($product->title) }}',
                                              icon: 'warning',
                                              showCancelButton: true,
                                              confirmButtonColor: '#ba1a1a',
                                              confirmButtonText: 'Ya, Hapus',
                                              cancelButtonText: 'Batal'
                                          }).then(r => { if(r.isConfirmed) $el.submit(); })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg bg-error-container/30 p-1.5 text-error transition hover:bg-error-container"
                                                title="Hapus Produk">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="border-t border-outline-variant/20 px-4 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

    {{-- ===== STATS BAR ===== --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
            Total: {{ number_format($stats['total']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">
            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> TikTok: {{ number_format($stats['tiktok']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">
            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Tokopedia: {{ number_format($stats['tokopedia']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Aktif: {{ number_format($stats['active']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-3 py-1.5 text-xs font-semibold text-yellow-700">
            <span class="h-1.5 w-1.5 rounded-full bg-yellow-400"></span> Nonaktif: {{ number_format($stats['deactivated']) }}
        </span>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700">
            <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span> Draft: {{ number_format($stats['draft']) }}
        </span>
    </div>

    {{-- ===== FILTER BAR ===== --}}
    <form method="GET" action="{{ route('products.index') }}">
        <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            {{-- Search --}}
            <div class="flex-1 min-w-50">
                <label class="mb-1 block text-xs font-medium text-slate-500">Cari Produk</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama produk, SKU, atau ID..."
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>
            </div>

            {{-- Akun --}}
            <div class="w-44">
                <label class="mb-1 block text-xs font-medium text-slate-500">Akun / Toko</label>
                <select name="account_id"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Semua Akun</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->shop_name ?? $acc->seller_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Platform --}}
            <div class="w-36">
                <label class="mb-1 block text-xs font-medium text-slate-500">Platform</label>
                <select name="platform"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="">Semua</option>
                    <option value="TIKTOK" {{ request('platform') === 'TIKTOK' ? 'selected' : '' }}>TikTok</option>
                    <option value="TOKOPEDIA" {{ request('platform') === 'TOKOPEDIA' ? 'selected' : '' }}>Tokopedia</option>
                </select>
            </div>

            {{-- Status --}}
            <div class="w-48">
                <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                <select name="status"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <option value="ALL"  {{ request('status', 'ALL') === 'ALL'  ? 'selected' : '' }}>Semua Status</option>
                    <option value="ACTIVATE"             {{ request('status') === 'ACTIVATE'             ? 'selected' : '' }}>✅ Aktif</option>
                    <option value="DRAFT"                {{ request('status') === 'DRAFT'                ? 'selected' : '' }}>📝 Draft</option>
                    <option value="PENDING"              {{ request('status') === 'PENDING'              ? 'selected' : '' }}>⏳ Pending</option>
                    <option value="FAILED"               {{ request('status') === 'FAILED'               ? 'selected' : '' }}>❌ Gagal</option>
                    <option value="SELLER_DEACTIVATED"   {{ request('status') === 'SELLER_DEACTIVATED'   ? 'selected' : '' }}>🔕 Nonaktif (Seller)</option>
                    <option value="PLATFORM_DEACTIVATED" {{ request('status') === 'PLATFORM_DEACTIVATED' ? 'selected' : '' }}>🚫 Nonaktif (Platform)</option>
                    <option value="FREEZE"               {{ request('status') === 'FREEZE'               ? 'selected' : '' }}>❄️ Frozen</option>
                    <option value="DELETED"              {{ request('status') === 'DELETED'              ? 'selected' : '' }}>🗑️ Dihapus</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request()->hasAny(['search', 'platform', 'status', 'account_id']))
                    <a href="{{ route('products.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- ===== PRODUCTS TABLE ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if($products->isEmpty())
            <div class="flex flex-col items-center p-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada produk</h3>
                <p class="mt-1 text-sm text-slate-500">Tambahkan akun marketplace dan sync produk untuk melihat data di sini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="px-4 py-3 font-semibold text-slate-600">#</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Produk</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Platform</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">SKU POS</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-right">Harga</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-center">Stok</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-center">Status</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($products as $product)
                        @php
                            $detail   = $product->detail;
                            $imgUrl   = null;
                            if ($detail && !empty($detail->main_images)) {
                                $imgs = is_array($detail->main_images) ? $detail->main_images : json_decode($detail->main_images, true);
                                $firstImg = $imgs[0] ?? null;
                                if (is_array($firstImg)) {
                                    $imgUrl = $firstImg['urls'][0] ?? $firstImg['url'] ?? $firstImg['thumb_url'] ?? null;
                                } elseif (is_string($firstImg)) {
                                    $imgUrl = $firstImg;
                                }
                            }
                            $statusConfig = [
                                'ACTIVATE'             => ['color' => 'bg-emerald-50 text-emerald-700',   'label' => 'Aktif'],
                                'DRAFT'                => ['color' => 'bg-amber-50 text-amber-700',       'label' => 'Draft'],
                                'PENDING'              => ['color' => 'bg-blue-50 text-blue-700',         'label' => 'Pending'],
                                'FAILED'               => ['color' => 'bg-red-100 text-red-700',          'label' => 'Gagal'],
                                'SELLER_DEACTIVATED'   => ['color' => 'bg-slate-100 text-slate-500',      'label' => 'Nonaktif'],
                                'PLATFORM_DEACTIVATED' => ['color' => 'bg-orange-50 text-orange-700',     'label' => 'Diblokir'],
                                'FREEZE'               => ['color' => 'bg-cyan-50 text-cyan-700',         'label' => 'Frozen'],
                                'DELETED'              => ['color' => 'bg-red-50 text-red-400',           'label' => 'Dihapus'],
                            ];
                            $cfg = $statusConfig[$product->product_status] ?? ['color' => 'bg-slate-100 text-slate-600', 'label' => $product->product_status];
                        @endphp
                        <tr class="transition hover:bg-blue-50/40">
                            {{-- # --}}
                            <td class="px-4 py-3 text-xs text-slate-400 font-mono align-top">
                                {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                            </td>

                            {{-- Produk (with image) --}}
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-3">
                                    {{-- Thumbnail --}}
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                        @if($imgUrl)
                                            <img src="{{ $imgUrl }}" alt="{{ $product->title }}" class="h-full w-full object-cover" loading="lazy"
                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                            <div class="hidden h-full w-full items-center justify-center text-slate-300">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('products.detail', $product->product_id) }}"
                                           class="block max-w-xs truncate font-medium text-slate-900 hover:text-blue-600 hover:underline"
                                           title="{{ $product->title }}">
                                            {{ $product->title }}
                                        </a>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400">
                                            @if($product->account)
                                                <span class="text-blue-500">{{ $product->account->shop_name ?? $product->account->seller_name }}</span>
                                                <span>•</span>
                                            @endif
                                            <span class="font-mono">ID: {{ $product->product_id ?: '-' }}</span>
                                            <span>•</span>
                                            <span class="font-mono">SKU: {{ $product->sku_id ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Platform --}}
                            <td class="px-4 py-3 align-top">
                                @if($product->platform === 'TIKTOK')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
                                        TikTok
                                    </span>
                                @elseif($product->platform === 'TOKOPEDIA')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Tokopedia
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ $product->platform }}
                                    </span>
                                @endif
                            </td>

                            {{-- SKU POS --}}
                            <td class="px-4 py-3 align-top">
                                @if($product->seller_sku)
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600">
                                        {{ $product->seller_sku }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>

                            {{-- Harga --}}
                            <td class="px-4 py-3 text-right align-top">
                                <span class="font-semibold text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            </td>

                            {{-- Stok --}}
                            <td class="px-4 py-3 text-center align-top">
                                @if($product->quantity > 0)
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                        {{ $product->quantity }}
                                    </span>
                                @else
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-red-50 px-2 py-1 text-xs font-bold text-red-600">
                                        0
                                    </span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $cfg['color'] }}"
                                      title="{{ $product->product_status }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-4 py-3 text-center align-top">
                                <div class="flex items-center justify-center gap-1">
                                    {{-- Detail / Sinkron --}}
                                    <a href="{{ route('products.detail', ['productId' => $product->product_id, 'account_id' => $product->account_id, 'refresh' => $detail ? null : 1]) }}"
                                       class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
                                       title="{{ $detail ? 'Lihat Detail' : 'Ambil Detail dari API' }}">
                                        @if($detail)
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        @endif
                                    </a>

                                    {{-- Edit --}}
                                    @if($detail)
                                    <a href="{{ route('products.edit', $product->product_id) }}"
                                       class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600"
                                       title="Edit Produk">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @endif

                                    {{-- Sinkron Ulang Detail --}}
                                    @if($detail)
                                    <a href="{{ route('products.detail', ['productId' => $product->product_id, 'account_id' => $product->account_id, 'refresh' => 1]) }}"
                                       class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                                       title="Sinkron Ulang Detail dari API">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </a>
                                    @endif

                                    {{-- Hapus --}}
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                                          x-data
                                          @submit.prevent="Swal.fire({
                                              title: 'Hapus Produk?',
                                              text: '{{ addslashes($product->title) }}',
                                              icon: 'warning',
                                              showCancelButton: true,
                                              confirmButtonColor: '#ef4444',
                                              confirmButtonText: 'Ya, Hapus',
                                              cancelButtonText: 'Batal'
                                          }).then(r => { if(r.isConfirmed) $el.submit(); })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                title="Hapus Produk">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="border-t border-slate-100 px-4 py-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
