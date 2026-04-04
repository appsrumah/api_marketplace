@extends('layouts.app')
@section('title', 'Produk Saya')

@section('content')

{{-- ===== HEADER ===== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Produk Saya</h1>
        <p class="mt-1 text-sm text-slate-500">Semua produk dari akun TikTok Shop yang terhubung.</p>
    </div>
</div>

{{-- ===== STATS BAR ===== --}}
<div class="mt-6 flex flex-wrap items-center gap-3">
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
<form method="GET" action="{{ route('products.index') }}" class="mt-6">
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

        {{-- Platform --}}
        <div class="w-40">
            <label class="mb-1 block text-xs font-medium text-slate-500">Platform</label>
            <select name="platform"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Semua</option>
                <option value="TIKTOK" {{ request('platform') === 'TIKTOK' ? 'selected' : '' }}>TikTok</option>
                <option value="TOKOPEDIA" {{ request('platform') === 'TOKOPEDIA' ? 'selected' : '' }}>Tokopedia</option>
            </select>
        </div>

        {{-- Status --}}
        <div class="w-52">
            <label class="mb-1 block text-xs font-medium text-slate-500">Status Produk</label>
            <select name="status"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="ALL"  {{ request('status', 'ALL') === 'ALL'  ? 'selected' : '' }}>Semua Status</option>
                <option value="ACTIVATE"             {{ request('status') === 'ACTIVATE'             ? 'selected' : '' }}>✅ Aktif</option>
                <option value="DRAFT"                {{ request('status') === 'DRAFT'                ? 'selected' : '' }}>📝 Draft</option>
                <option value="PENDING"              {{ request('status') === 'PENDING'              ? 'selected' : '' }}>⏳ Pending Review</option>
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
<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @if($products->isEmpty())
        <div class="flex flex-col items-center p-12 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada produk</h3>
            <p class="mt-1 text-sm text-slate-500">Tambahkan akun TikTok dan sync produk untuk melihat data di sini.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="px-4 py-3 font-semibold text-slate-600">#</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Produk</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Product ID</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">SKU ID</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Platform</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">SKU POS</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Harga</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Stok</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Status</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Sinkron</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($products as $product)
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-4 py-3 text-xs text-slate-400 font-mono">
                                {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="max-w-xs">
                                    <p class="truncate font-medium text-slate-900">{{ $product->title }}</p>
                                    @if($product->account)
                                        <p class="mt-0.5 text-[10px] text-blue-500">{{ $product->account->seller_name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-500">{{ $product->product_id ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-500">{{ $product->sku_id ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($product->platform === 'TIKTOK')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
                                        TikTok
                                    </span>
                                @elseif($product->platform === 'TOKOPEDIA')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        Tokopedia
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        {{ $product->platform }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-slate-600">{{ $product->seller_sku ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-slate-900">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
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
                            <td class="px-4 py-3 text-center">
                                @php
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
                                    $cfg   = $statusConfig[$product->product_status] ?? ['color' => 'bg-slate-100 text-slate-600', 'label' => $product->product_status];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $cfg['color'] }}"
                                      title="{{ $product->product_status }}">
                                    {{ $cfg['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="text-[10px] text-slate-500 leading-relaxed">
                                    <div title="Pertama disinkron">
                                        <span class="font-medium text-slate-400">1st:</span>
                                        {{ $product->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </div>
                                    <div title="Terakhir diperbarui">
                                        <span class="font-medium text-slate-400">Upd:</span>
                                        {{ $product->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                    </div>
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

@endsection
