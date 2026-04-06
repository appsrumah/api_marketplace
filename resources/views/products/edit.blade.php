@extends('layouts.app')
@section('title', 'Edit Produk — ' . Str::limit($detail->title, 30))

@section('content')

{{-- ===== BACK + TITLE ===== --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('products.detail', $detail->product_id) }}"
       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
    <div>
        <h1 class="text-xl font-bold text-slate-900">Edit Produk</h1>
        <p class="text-xs font-mono text-slate-400">ID: {{ $detail->product_id }}</p>
    </div>
    <span class="ml-auto inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $detail->status_color }}">
        {{ $detail->status_label }}
    </span>
</div>

<form method="POST" action="{{ route('products.update', $detail->product_id) }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- ===== LEFT: Edit Form ===== --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Basic Info --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-700">Informasi Dasar</h2>
                </div>
                <div class="px-5 py-5 space-y-5">
                    {{-- Title --}}
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Judul Produk <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title"
                               value="{{ old('title', $detail->title) }}"
                               required maxlength="255"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100
                                      @error('title') border-red-400 focus:border-red-400 focus:ring-red-100 @enderror">
                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="8"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('description', $detail->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- SKU Price Edit --}}
            @if($detail->skus && count($detail->skus) > 0)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-700">Varian / SKU</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Anda bisa mengedit harga per varian.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/30">
                                <th class="px-4 py-2.5 font-semibold text-slate-600">#</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-600">Seller SKU</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-600 text-center">Stok</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-600 text-right">Harga Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($detail->skus as $i => $sku)
                                <tr>
                                    <td class="px-4 py-3 text-xs text-slate-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-mono text-xs text-slate-700">{{ $sku['seller_sku'] ?? '-' }}</p>
                                        <p class="text-[10px] text-slate-400">ID: {{ $sku['id'] ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php $qty = $sku['inventory'][0]['quantity'] ?? 0; @endphp
                                        <span class="inline-flex min-w-6 items-center justify-center rounded-lg px-2 py-0.5 text-xs font-bold {{ $qty > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">
                                            {{ $qty }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @php $price = $sku['price']['tax_exclusive_price'] ?? $sku['price']['sale_price'] ?? 0; @endphp
                                        <span class="font-semibold text-slate-900">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- ===== RIGHT: Preview & Submit ===== --}}
        <div class="space-y-6">

            {{-- Preview Images --}}
            @if($detail->main_images && count($detail->main_images) > 0)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-700">Gambar</h2>
                </div>
                <div class="p-4">
                    <div class="flex flex-wrap gap-2">
                        @foreach($detail->main_images as $img)
                            @php $url = is_array($img) ? ($img['url'] ?? ($img['urls'][0] ?? '')) : $img; @endphp
                            @if($url)
                                <img src="{{ $url }}" alt="" class="h-16 w-16 rounded-lg border border-slate-200 object-cover">
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-2 text-[10px] text-slate-400">Upload gambar baru belum didukung. Gunakan TikTok Seller Center.</p>
                </div>
            </div>
            @endif

            {{-- Product Info Summary --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                    <h2 class="text-sm font-semibold text-slate-700">Info</h2>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Kategori</span><span class="text-slate-700">{{ $detail->category_name ?: '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Brand</span><span class="text-slate-700">{{ $detail->brand_name ?: '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Toko</span><span class="text-slate-700">{{ $detail->account?->shop_name ?: '-' }}</span></div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                <p class="text-xs text-blue-700 mb-3">
                    Perubahan akan langsung di-push ke TikTok Shop API. Pastikan data sudah benar sebelum menyimpan.
                </p>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan & Push ke TikTok
                </button>
            </div>
        </div>
    </div>
</form>

@endsection
