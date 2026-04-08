@extends('layouts.app')
@section('title', 'Detail Produk — ' . Str::limit($detail->title, 30))

@section('content')

{{-- ===== BACK + TITLE ===== --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('products.index') }}"
       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
    <div class="flex-1 min-w-0">
        <h1 class="text-xl font-bold text-slate-900 truncate">{{ $detail->title }}</h1>
        <p class="text-xs font-mono text-slate-400">ID: {{ $detail->product_id }}</p>
    </div>
    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $detail->status_color }}">
        {{ $detail->status_label }}
    </span>
    <a href="{{ route('products.edit', $detail->product_id) }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit
    </a>
    <a href="{{ route('products.detail', [$detail->product_id, 'refresh' => 1, 'account_id' => $detail->account_id]) }}"
       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Refresh
    </a>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- ===== LEFT: Main content ===== --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Images --}}
        @if($detail->main_images && count($detail->main_images) > 0)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Gambar Produk</h2>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-3">
                    @foreach($detail->main_images as $img)
                        @php $url = is_array($img) ? ($img['url'] ?? ($img['urls'][0] ?? '')) : $img; @endphp
                        @if($url)
                            <img src="{{ $url }}" alt="" class="h-24 w-24 rounded-xl border border-slate-200 object-cover transition hover:scale-105 hover:shadow-lg cursor-pointer">
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Description --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Deskripsi</h2>
            </div>
            <div class="px-5 py-4 prose prose-sm max-w-none text-slate-700">
                {!! nl2br(e($detail->description ?: 'Tidak ada deskripsi.')) !!}
            </div>
        </div>

        {{-- SKUs --}}
        @if($detail->skus && count($detail->skus) > 0)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Varian / SKU ({{ count($detail->skus) }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/30">
                            <th class="px-4 py-2.5 font-semibold text-slate-600">#</th>
                            <th class="px-4 py-2.5 font-semibold text-slate-600">SKU ID</th>
                            <th class="px-4 py-2.5 font-semibold text-slate-600">Seller SKU</th>
                            <th class="px-4 py-2.5 font-semibold text-slate-600 text-right">Harga</th>
                            <th class="px-4 py-2.5 font-semibold text-slate-600 text-center">Stok</th>
                            <th class="px-4 py-2.5 font-semibold text-slate-600 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($detail->skus as $i => $sku)
                            <tr class="hover:bg-blue-50/30">
                                <td class="px-4 py-2.5 text-xs text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $sku['id'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 font-mono text-xs text-slate-700">{{ $sku['seller_sku'] ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-900">
                                    @php
                                        $price = $sku['price']['tax_exclusive_price'] ?? $sku['price']['sale_price'] ?? $sku['price']['original_price'] ?? 0;
                                    @endphp
                                    Rp {{ number_format($price, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php $qty = $sku['inventory'][0]['quantity'] ?? 0; @endphp
                                    <span class="inline-flex min-w-6 items-center justify-center rounded-lg px-2 py-0.5 text-xs font-bold {{ $qty > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                                        {{ $qty }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @php $st = $sku['status_info']['status'] ?? '-'; @endphp
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $st === 'ACTIVATE' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">
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
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Atribut Produk</h2>
            </div>
            <div class="px-5 py-4">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($detail->product_attributes as $attr)
                        <div class="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <span class="text-slate-500">{{ $attr['name'] ?? $attr['id'] ?? '?' }}</span>
                            <span class="font-medium text-slate-700">
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

    {{-- ===== RIGHT: Sidebar ===== --}}
    <div class="space-y-6">

        {{-- Product Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Info Produk</h2>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block">Kategori</span>
                    <span class="text-slate-700">{{ $detail->category_name ?: '-' }}</span>
                </div>
                @if($detail->brand_name)
                    <div>
                        <span class="text-xs text-slate-400 block">Brand</span>
                        <span class="text-slate-700">{{ $detail->brand_name }}</span>
                    </div>
                @endif
                <div>
                    <span class="text-xs text-slate-400 block">Toko</span>
                    <span class="font-medium text-slate-700">{{ $detail->account?->shop_name ?: '-' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block">Platform</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">{{ $detail->platform }}</span>
                </div>
            </div>
        </div>

        {{-- Package Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Paket & Dimensi</h2>
            </div>
            <div class="px-5 py-4 space-y-2 text-sm">
                @if($detail->package_weight)
                    <div class="flex justify-between"><span class="text-slate-500">Berat</span><span class="text-slate-900">{{ $detail->package_weight }} g</span></div>
                @endif
                @if($detail->package_length || $detail->package_width || $detail->package_height)
                    <div class="flex justify-between"><span class="text-slate-500">Dimensi</span>
                        <span class="text-slate-900">{{ $detail->package_length ?? '-' }} × {{ $detail->package_width ?? '-' }} × {{ $detail->package_height ?? '-' }} {{ $detail->package_dimensions_unit }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Delivery Options --}}
        @if($detail->delivery_options && count($detail->delivery_options) > 0)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Opsi Pengiriman</h2>
            </div>
            <div class="px-5 py-4 space-y-1.5">
                @foreach($detail->delivery_options as $opt)
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="h-3.5 w-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-slate-700">{{ $opt['name'] ?? $opt['id'] ?? json_encode($opt) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Timeline</h2>
            </div>
            <div class="px-5 py-4 space-y-2 text-xs">
                @if($detail->tiktok_create_time)
                    <div class="flex justify-between"><span class="text-slate-500">Dibuat (TikTok)</span><span class="text-slate-700">{{ $detail->created_at_tiktok?->format('d M Y H:i') }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-slate-500">Disimpan Lokal</span><span class="text-slate-700">{{ $detail->created_at?->format('d M Y H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Terakhir Update</span><span class="text-slate-700">{{ $detail->updated_at?->format('d M Y H:i') }}</span></div>
            </div>
        </div>
    </div>
</div>

@endsection
