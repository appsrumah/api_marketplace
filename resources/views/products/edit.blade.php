@extends('layouts.app')
@section('title', 'Edit Produk — ' . Str::limit($detail->title, 30))
@section('breadcrumb', 'Produk — Edit')

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     BACK + TITLE
═══════════════════════════════════════════════════════════════ --}}
<div class="mb-6 flex flex-wrap items-center gap-3">
    <a href="{{ route('products.detail', $detail->product_id) }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Kembali
    </a>
    <div>
        <h1 class="font-headline text-2xl font-bold tracking-tight text-primary">Edit Produk</h1>
        <p class="mt-0.5 font-mono text-xs text-on-surface-variant/60">ID: {{ $detail->product_id }}</p>
    </div>
    <span class="ml-auto inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $detail->status_color }}">
        {{ $detail->status_label }}
    </span>
</div>

<form method="POST" action="{{ route('products.update', $detail->product_id) }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- ═══════════════════════════════════════════════════════════
             LEFT: Edit Form
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Basic Info --}}
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h2 class="text-sm font-bold text-on-surface">Informasi Dasar</h2>
                </div>
                <div class="space-y-5 px-5 py-5">
                    {{-- Title --}}
                    <div>
                        <label for="title" class="mb-1.5 block text-sm font-semibold text-on-surface">
                            Judul Produk <span class="text-error">*</span>
                        </label>
                        <input type="text" name="title" id="title"
                               value="{{ old('title', $detail->title) }}"
                               required maxlength="255"
                               class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-2.5 text-sm text-on-surface shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/10 focus:outline-none
                                      @error('title') border-error focus:border-error focus:ring-error/10 @enderror">
                        @error('title')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-semibold text-on-surface">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="8"
                                  class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-2.5 text-sm text-on-surface shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/10 focus:outline-none">{{ old('description', $detail->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- SKU Price Edit --}}
            @if($detail->skus && count($detail->skus) > 0)
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h2 class="text-sm font-bold text-on-surface">Varian / SKU</h2>
                    <p class="mt-0.5 text-xs text-on-surface-variant">Anda bisa mengedit harga per varian.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-outline-variant/20 bg-surface-container-low/60">
                                <th class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">#</th>
                                <th class="px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Seller SKU</th>
                                <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Stok</th>
                                <th class="px-4 py-2.5 text-right text-xs font-bold uppercase tracking-wider text-on-surface-variant">Harga Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($detail->skus as $i => $sku)
                                <tr class="transition hover:bg-surface-container-low">
                                    <td class="px-4 py-3 text-xs text-on-surface-variant">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-mono text-xs text-on-surface">{{ $sku['seller_sku'] ?? '-' }}</p>
                                        <p class="text-[10px] text-on-surface-variant/60">ID: {{ $sku['id'] ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php $qty = $sku['inventory'][0]['quantity'] ?? 0; @endphp
                                        <span class="inline-flex min-w-6 items-center justify-center rounded-lg px-2 py-0.5 text-xs font-bold {{ $qty > 0 ? 'bg-secondary-container/50 text-on-secondary-container' : 'bg-error-container/40 text-on-error-container' }}">
                                            {{ $qty }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @php $price = $sku['price']['tax_exclusive_price'] ?? $sku['price']['sale_price'] ?? 0; @endphp
                                        <span class="font-semibold text-on-surface">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             RIGHT: Preview & Submit
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- Preview Images --}}
            @if($detail->main_images && count($detail->main_images) > 0)
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h2 class="text-sm font-bold text-on-surface">Gambar</h2>
                </div>
                <div class="p-4">
                    <div class="flex flex-wrap gap-2">
                        @foreach($detail->main_images as $img)
                            @php $url = is_array($img) ? ($img['url'] ?? ($img['urls'][0] ?? '')) : $img; @endphp
                            @if($url)
                                <img src="{{ $url }}" alt="" class="h-16 w-16 rounded-lg border border-outline-variant/30 object-cover">
                            @endif
                        @endforeach
                    </div>
                    <p class="mt-2 text-[10px] text-on-surface-variant/60">Upload gambar baru belum didukung. Gunakan TikTok Seller Center.</p>
                </div>
            </div>
            @endif

            {{-- Product Info Summary --}}
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h2 class="text-sm font-bold text-on-surface">Info</h2>
                </div>
                <div class="space-y-2 px-5 py-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Kategori</span>
                        <span class="text-on-surface">{{ $detail->category_name ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Brand</span>
                        <span class="text-on-surface">{{ $detail->brand_name ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-on-surface-variant">Toko</span>
                        <span class="text-on-surface">{{ $detail->account?->shop_name ?: '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="rounded-2xl bg-primary-fixed p-5">
                <p class="mb-3 text-xs text-on-surface-variant">
                    Perubahan akan langsung di-push ke TikTok Shop API. Pastikan data sudah benar sebelum menyimpan.
                </p>
                <button type="submit"
                        class="primary-gradient w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                    Simpan &amp; Push ke TikTok
                </button>
            </div>
        </div>
    </div>
</form>

@endsection
