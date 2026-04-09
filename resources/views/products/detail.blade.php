@extends('layouts.app')
@section('title', 'Detail Produk â€” ' . Str::limit($detail->title, 30))
@section('breadcrumb', 'Produk â€” Detail')

@section('content')

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     BACK + TITLE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="mb-6 flex flex-wrap items-start gap-3">
    <a href="{{ route('products.index') }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Kembali
    </a>
    <div class="flex-1 min-w-0">
        <h1 class="font-headline text-2xl font-bold tracking-tight text-primary truncate">{{ $detail->title }}</h1>
        <p class="mt-0.5 font-mono text-xs text-on-surface-variant/60">ID: {{ $detail->product_id }}</p>
    </div>
    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $detail->status_color }}">
        {{ $detail->status_label }}
    </span>
    <a href="{{ route('products.edit', $detail->product_id) }}"
       class="primary-gradient inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90">
        <span class="material-symbols-outlined text-[18px]">edit</span>
        Edit
    </a>
    <a href="{{ route('products.detail', [$detail->product_id, 'refresh' => 1, 'account_id' => $detail->account_id]) }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-4 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">refresh</span>
        Refresh
    </a>
</div>

<div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         LEFT: Main content
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Images --}}
        @if($detail->main_images && count($detail->main_images) > 0)
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Gambar Produk</h2>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-3">
                    @foreach($detail->main_images as $img)
                        @php $url = is_array($img) ? ($img['url'] ?? ($img['urls'][0] ?? '')) : $img; @endphp
                        @if($url)
                            <img src="{{ $url }}" alt="" class="h-24 w-24 cursor-pointer rounded-xl border border-outline-variant/30 object-cover transition hover:scale-105 hover:shadow-lg">
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Description --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Deskripsi</h2>
            </div>
            <div class="prose prose-sm max-w-none px-5 py-4 text-on-surface-variant">
                {!! nl2br(e($detail->description ?: 'Tidak ada deskripsi.')) !!}
            </div>
        </div>

        {{-- SKUs --}}
        @if($detail->skus && count($detail->skus) > 0)
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">
                    Varian / SKU
                    <span class="ml-1.5 inline-flex rounded-full bg-primary-fixed px-2 py-0.5 text-xs font-bold text-primary">{{ count($detail->skus) }}</span>
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/20 bg-surface-container-low/60">
                            <th class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">#</th>
                            <th class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">SKU ID</th>
                            <th class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Seller SKU</th>
                            <th class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-on-surface-variant">Harga</th>
                            <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Stok</th>
                            <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @foreach($detail->skus as $i => $sku)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-4 py-2.5 text-xs text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-on-surface-variant/70">{{ $sku['id'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-on-surface">{{ $sku['seller_sku'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-on-surface">
                                    @php
                                        $price = $sku['price']['tax_exclusive_price'] ?? $sku['price']['sale_price'] ?? $sku['price']['original_price'] ?? 0;
                                    @endphp
                                    Rp {{ number_format($price, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php $qty = $sku['inventory'][0]['quantity'] ?? 0; @endphp
                                    <span class="inline-flex min-w-6 items-center justify-center rounded-lg px-2 py-0.5 text-xs font-bold {{ $qty > 0 ? 'bg-secondary-container/50 text-on-secondary-container' : 'bg-error-container/40 text-on-error-container' }}">
                                        {{ $qty }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php $st = $sku['status_info']['status'] ?? '-'; @endphp
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $st === 'ACTIVATE' ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant' }}">
                                        {{ $st }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Attributes --}}
        @if($detail->product_attributes && count($detail->product_attributes) > 0)
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Atribut Produk</h2>
            </div>
            <div class="px-5 py-4">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($detail->product_attributes as $attr)
                        <div class="flex justify-between rounded-xl bg-surface-container-low px-3 py-2 text-sm">
                            <span class="text-on-surface-variant">{{ $attr['name'] ?? $attr['id'] ?? '?' }}</span>
                            <span class="font-medium text-on-surface">
                                @if(isset($attr['values']))
                                    {{ collect($attr['values'])->pluck('name')->implode(', ') }}
                                @else
                                    {{ $attr['value'] ?? '-' }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         RIGHT: Sidebar
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="space-y-8">

        {{-- Product Info --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Info Produk</h2>
            </div>
            <div class="space-y-3 px-5 py-4 text-sm">
                <div>
                    <span class="block text-xs font-medium text-on-surface-variant">Kategori</span>
                    <span class="text-on-surface">{{ $detail->category_name ?: '-' }}</span>
                </div>
                @if($detail->brand_name)
                    <div>
                        <span class="block text-xs font-medium text-on-surface-variant">Brand</span>
                        <span class="text-on-surface">{{ $detail->brand_name }}</span>
                    </div>
                @endif
                <div>
                    <span class="block text-xs font-medium text-on-surface-variant">Toko</span>
                    <span class="font-medium text-on-surface">{{ $detail->account?->shop_name ?: '-' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-medium text-on-surface-variant">Platform</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold text-primary">{{ $detail->platform }}</span>
                </div>
            </div>
        </div>

        {{-- Package Info --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Paket &amp; Dimensi</h2>
            </div>
            <div class="space-y-2 px-5 py-4 text-sm">
                @if($detail->package_weight)
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Berat</span>
                        <span class="font-medium text-on-surface">{{ $detail->package_weight }} g</span>
                    </div>
                @endif
                @if($detail->package_length || $detail->package_width || $detail->package_height)
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Dimensi</span>
                        <span class="font-medium text-on-surface">{{ $detail->package_length ?? '-' }} Ã— {{ $detail->package_width ?? '-' }} Ã— {{ $detail->package_height ?? '-' }} {{ $detail->package_dimensions_unit }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Delivery Options --}}
        @if($detail->delivery_options && count($detail->delivery_options) > 0)
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Opsi Pengiriman</h2>
            </div>
            <div class="space-y-1.5 px-5 py-4">
                @foreach($detail->delivery_options as $opt)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-[16px] text-secondary">check_circle</span>
                        <span class="text-on-surface">{{ $opt['name'] ?? $opt['id'] ?? json_encode($opt) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Timeline</h2>
            </div>
            <div class="space-y-2 px-5 py-4 text-xs">
                @if($detail->tiktok_create_time)
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Dibuat (TikTok)</span>
                        <span class="text-on-surface">{{ $detail->created_at_tiktok?->format('d M Y H:i') }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Disimpan Lokal</span>
                    <span class="text-on-surface">{{ $detail->created_at?->format('d M Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Terakhir Update</span>
                    <span class="text-on-surface">{{ $detail->updated_at?->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
