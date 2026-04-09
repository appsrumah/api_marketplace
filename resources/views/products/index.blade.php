@extends('layouts.app')
@section('title', 'Produk Saya')
@section('breadcrumb', 'Produk â€” Manajemen Katalog')

@section('content')
<div class="space-y-8">

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
                    <option value="ACTIVATE"             {{ request('status') === 'ACTIVATE'             ? 'selected' : '' }}>âœ… Aktif</option>
                    <option value="DRAFT"                {{ request('status') === 'DRAFT'                ? 'selected' : '' }}>ðŸ“ Draft</option>
                    <option value="PENDING"              {{ request('status') === 'PENDING'              ? 'selected' : '' }}>â³ Pending</option>
                    <option value="FAILED"               {{ request('status') === 'FAILED'               ? 'selected' : '' }}>âŒ Gagal</option>
                    <option value="SELLER_DEACTIVATED"   {{ request('status') === 'SELLER_DEACTIVATED'   ? 'selected' : '' }}>ðŸ”• Nonaktif (Seller)</option>
                    <option value="PLATFORM_DEACTIVATED" {{ request('status') === 'PLATFORM_DEACTIVATED' ? 'selected' : '' }}>ðŸš« Nonaktif (Platform)</option>
                    <option value="FREEZE"               {{ request('status') === 'FREEZE'               ? 'selected' : '' }}>â„ï¸ Frozen</option>
                    <option value="DELETED"              {{ request('status') === 'DELETED'              ? 'selected' : '' }}>ðŸ—‘ï¸ Dihapus</option>
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
                                                <span>â€¢</span>
                                            @endif
                                            <span class="font-mono">ID: {{ $product->product_id ?: '-' }}</span>
                                            <span>â€¢</span>
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
                                    <span class="text-xs text-on-surface-variant/40">â€”</span>
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
