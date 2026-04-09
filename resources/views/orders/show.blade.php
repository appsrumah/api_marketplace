@extends('layouts.app')
@section('title', 'Detail Pesanan #' . Str::limit($order->order_id, 12))
@section('breadcrumb', 'Pesanan — Detail Order')

@section('content')

{{-- ===== BACK + TITLE ===== --}}
<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container-high">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        Kembali
    </a>
    <div>
        <h1 class="font-headline text-xl font-bold text-primary">Detail Pesanan</h1>
        <p class="font-mono text-xs text-on-surface-variant">{{ $order->order_id }}</p>
    </div>
    <span class="ml-auto inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $order->status_color }}">
        {{ $order->status_label }}
    </span>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- ===== LEFT: Order Info ===== --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- Order Items --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Produk Dipesan ({{ $order->items->count() }})</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="divide-y divide-outline-variant/10">
                @forelse($order->items as $item)
                    <div class="flex items-center gap-4 px-5 py-4">
                        @if($item->product_image)
                            <img src="{{ $item->product_image }}" alt="" class="h-14 w-14 rounded-xl object-cover ring-1 ring-outline-variant/20">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-fixed">
                                <span class="material-symbols-outlined text-[24px] text-primary">image</span>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-on-surface">{{ $item->product_name ?: 'Produk' }}</p>
                            <p class="mt-0.5 text-xs text-on-surface-variant">
                                SKU: {{ $item->seller_sku ?: $item->sku_id }}
                                @if($item->sku_name) — {{ $item->sku_name }} @endif
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-on-surface">Rp {{ number_format($item->sale_price, 0, ',', '.') }}</p>
                            <p class="text-xs text-on-surface-variant">× {{ $item->quantity }}</p>
                        </div>
                        <div class="w-28 shrink-0 text-right">
                            <p class="text-sm font-bold text-on-surface">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            @if($item->total_discount > 0)
                                <p class="text-[10px] text-secondary">-Rp {{ number_format($item->total_discount, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-on-surface-variant">Tidak ada item.</div>
                @endforelse
            </div>
        </div>

        {{-- Payment Summary --}}
        <div class="rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Ringkasan Pembayaran</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="space-y-2 px-5 py-4 text-sm">
                <div class="flex justify-between"><span class="text-on-surface-variant">Subtotal</span><span class="text-on-surface">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Ongkos Kirim</span><span class="text-on-surface">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span></div>
                @if($order->seller_discount > 0)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Diskon Seller</span><span class="text-secondary">-Rp {{ number_format($order->seller_discount, 0, ',', '.') }}</span></div>
                @endif
                @if($order->platform_discount > 0)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Diskon Platform</span><span class="text-secondary">-Rp {{ number_format($order->platform_discount, 0, ',', '.') }}</span></div>
                @endif
                <div class="flex justify-between border-t border-outline-variant/20 pt-2 font-bold">
                    <span class="text-on-surface">Total</span>
                    <span class="font-headline text-lg text-primary">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                @if($order->payment_method)
                    <div class="flex justify-between pt-1 text-xs text-on-surface-variant">
                        <span>Metode Bayar</span><span>{{ $order->payment_method }} @if($order->is_cod) (COD) @endif</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: Sidebar ===== --}}
    <div class="space-y-6">

        {{-- Buyer Info --}}
        <div class="rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Info Pembeli</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="space-y-3 px-5 py-4 text-sm">
                <div>
                    <span class="block text-xs text-on-surface-variant">Nama</span>
                    <span class="font-medium text-on-surface">{{ $order->buyer_name ?: '-' }}</span>
                </div>
                @if($order->buyer_phone)
                    <div>
                        <span class="block text-xs text-on-surface-variant">Telepon</span>
                        <span class="font-mono text-on-surface">{{ $order->buyer_phone }}</span>
                    </div>
                @endif
                @if($order->buyer_message)
                    <div>
                        <span class="block text-xs text-on-surface-variant">Pesan</span>
                        <span class="text-on-surface">{{ $order->buyer_message }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Shipping Info --}}
        <div class="rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Pengiriman</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="space-y-3 px-5 py-4 text-sm">
                @if($order->shipping_provider)
                    <div>
                        <span class="block text-xs text-on-surface-variant">Kurir</span>
                        <span class="font-medium text-on-surface">{{ $order->shipping_provider }}</span>
                    </div>
                @endif
                @if($order->tracking_number)
                    <div>
                        <span class="block text-xs text-on-surface-variant">No. Resi</span>
                        <span class="font-mono text-primary">{{ $order->tracking_number }}</span>
                    </div>
                @endif
                @if($order->shipping_address)
                    <div>
                        <span class="block text-xs text-on-surface-variant">Alamat</span>
                        @php $addr = $order->shipping_address; @endphp
                        <span class="leading-relaxed text-on-surface">
                            {{ $addr['name'] ?? '' }}<br>
                            {{ $addr['full_address'] ?? ($addr['address_detail'] ?? '') }}<br>
                            {{ $addr['district_info'][2]['address_name'] ?? '' }}
                            {{ $addr['district_info'][1]['address_name'] ?? '' }}
                            {{ $addr['district_info'][0]['address_name'] ?? '' }}
                            {{ $addr['postal_code'] ?? '' }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Timeline</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="space-y-2.5 px-5 py-4 text-xs">
                @if($order->tiktok_create_time)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Dibuat</span><span class="text-on-surface">{{ $order->created_at_tiktok?->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->paid_at)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Dibayar</span><span class="text-secondary">{{ $order->paid_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->shipped_at)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Dikirim</span><span class="text-primary">{{ $order->shipped_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->delivered_at)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Terkirim</span><span class="text-secondary">{{ $order->delivered_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->completed_at)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Selesai</span><span class="font-semibold text-secondary">{{ $order->completed_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->cancelled_at)
                    <div class="flex justify-between"><span class="text-on-surface-variant">Dibatalkan</span><span class="text-error">{{ $order->cancelled_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->cancel_reason)
                    <div class="mt-2 rounded-xl bg-error-container/30 p-2 text-[10px] text-on-error-container">{{ $order->cancel_reason }}</div>
                @endif
            </div>
        </div>

        {{-- Account Info --}}
        <div class="rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Akun</h2>
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="space-y-2 px-5 py-4 text-sm">
                <div class="flex justify-between"><span class="text-on-surface-variant">Toko</span><span class="font-medium text-on-surface">{{ $order->account?->shop_name ?: '-' }}</span></div>
                <div class="flex justify-between"><span class="text-on-surface-variant">Platform</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-semibold text-primary">{{ $order->platform }}</span>
                </div>
            </div>
        </div>

        {{-- POS Integration Card --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="flex items-center justify-between bg-surface-container-low px-5 py-3.5">
                <h2 class="text-sm font-bold text-on-surface">Sinkron ke POS</h2>
                @if($order->is_synced_to_pos)
                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-0.5 text-[10px] font-semibold text-on-secondary-container">
                        <span class="material-symbols-outlined text-[12px]">check_circle</span>
                        Tersinkron
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-[10px] font-semibold text-on-tertiary-fixed-variant">Belum Sync</span>
                @endif
            </div>
            <div class="h-px bg-outline-variant/20"></div>
            <div class="px-5 py-4">
                @if($order->is_synced_to_pos)
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-on-surface-variant">SO ID (POS)</span>
                            <span class="font-mono font-semibold text-on-surface">{{ $order->pos_order_id ?: '-' }}</span>
                        </div>
                        @if($order->synced_to_pos_at)
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant">Waktu Sync</span>
                                <span class="text-xs text-on-surface">{{ $order->synced_to_pos_at->format('d M Y H:i') }}</span>
                            </div>
                        @endif
                        <div class="mt-1 flex items-center gap-2 rounded-xl bg-secondary-container/30 px-3 py-2.5 text-xs text-on-secondary-container">
                            <span class="material-symbols-outlined shrink-0 text-[16px]">check_circle</span>
                            Data order sudah masuk ke database POS.
                        </div>
                    </div>
                @elseif(in_array($order->order_status, ['UNPAID', 'CANCELLED']))
                    <div class="flex items-center gap-2 rounded-xl bg-surface-container-high px-3 py-3 text-xs text-on-surface-variant">
                        <span class="material-symbols-outlined shrink-0 text-[16px]">block</span>
                        Order <strong class="text-on-surface">{{ $order->status_label }}</strong> tidak dapat di-push ke POS.
                    </div>
                @else
                    <p class="mb-4 text-xs leading-relaxed text-on-surface-variant">
                        Klik tombol di bawah untuk mengirim data order ini ke database POS sebagai <strong class="text-on-surface">Sales Order (SO)</strong>.
                        Item dicocokkan berdasarkan <strong class="text-on-surface">Seller SKU</strong> yang sesuai di POS.
                    </p>
                    @if(!$order->account?->id_outlet)
                        <div class="mb-3 flex items-center gap-2 rounded-xl bg-tertiary-fixed px-3 py-2.5 text-xs text-on-tertiary-fixed-variant">
                            <span class="material-symbols-outlined shrink-0 text-[16px]">warning</span>
                            Akun belum punya ID Outlet. Atur di <a href="{{ route('integrations.show', $order->account) }}" class="font-semibold underline">halaman Integrasi</a>.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('orders.push-pos', $order) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Push order ini ke POS?\n\nItem dicocokkan berdasarkan Seller SKU.\nPastikan akun sudah memiliki ID Outlet.')"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                @if(!$order->account?->id_outlet) disabled @endif>
                            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                            Push ke POS Sekarang
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- ===== LEFT: Order Info ===== --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Order Items --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Produk Dipesan ({{ $order->items->count() }})</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($order->items as $item)
                    <div class="flex items-center gap-4 px-5 py-4">
                        @if($item->product_image)
                            <img src="{{ $item->product_image }}" alt="" class="h-14 w-14 rounded-xl border border-slate-200 object-cover">
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $item->product_name ?: 'Produk' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                SKU: {{ $item->seller_sku ?: $item->sku_id }}
                                @if($item->sku_name) — {{ $item->sku_name }} @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-slate-900">Rp {{ number_format($item->sale_price, 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-400">× {{ $item->quantity }}</p>
                        </div>
                        <div class="text-right shrink-0 w-28">
                            <p class="text-sm font-bold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            @if($item->total_discount > 0)
                                <p class="text-[10px] text-green-600">-Rp {{ number_format($item->total_discount, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada item.</div>
                @endforelse
            </div>
        </div>

        {{-- Payment Summary --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Ringkasan Pembayaran</h2>
            </div>
            <div class="px-5 py-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="text-slate-900">Rp {{ number_format($order->subtotal_amount, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Ongkos Kirim</span><span class="text-slate-900">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span></div>
                @if($order->seller_discount > 0)
                    <div class="flex justify-between"><span class="text-slate-500">Diskon Seller</span><span class="text-green-600">-Rp {{ number_format($order->seller_discount, 0, ',', '.') }}</span></div>
                @endif
                @if($order->platform_discount > 0)
                    <div class="flex justify-between"><span class="text-slate-500">Diskon Platform</span><span class="text-green-600">-Rp {{ number_format($order->platform_discount, 0, ',', '.') }}</span></div>
                @endif
                <div class="flex justify-between border-t border-slate-100 pt-2 font-bold">
                    <span class="text-slate-700">Total</span>
                    <span class="text-lg text-blue-700">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                @if($order->payment_method)
                    <div class="flex justify-between text-xs text-slate-400 pt-1">
                        <span>Metode Bayar</span><span>{{ $order->payment_method }} @if($order->is_cod) (COD) @endif</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== RIGHT: Sidebar ===== --}}
    <div class="space-y-6">

        {{-- Buyer Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Info Pembeli</h2>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block">Nama</span>
                    <span class="font-medium text-slate-900">{{ $order->buyer_name ?: '-' }}</span>
                </div>
                @if($order->buyer_phone)
                    <div>
                        <span class="text-xs text-slate-400 block">Telepon</span>
                        <span class="font-mono text-slate-700">{{ $order->buyer_phone }}</span>
                    </div>
                @endif
                @if($order->buyer_message)
                    <div>
                        <span class="text-xs text-slate-400 block">Pesan</span>
                        <span class="text-slate-700">{{ $order->buyer_message }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Shipping Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Pengiriman</h2>
            </div>
            <div class="px-5 py-4 space-y-3 text-sm">
                @if($order->shipping_provider)
                    <div>
                        <span class="text-xs text-slate-400 block">Kurir</span>
                        <span class="font-medium text-slate-900">{{ $order->shipping_provider }}</span>
                    </div>
                @endif
                @if($order->tracking_number)
                    <div>
                        <span class="text-xs text-slate-400 block">No. Resi</span>
                        <span class="font-mono text-blue-600">{{ $order->tracking_number }}</span>
                    </div>
                @endif
                @if($order->shipping_address)
                    <div>
                        <span class="text-xs text-slate-400 block">Alamat</span>
                        @php $addr = $order->shipping_address; @endphp
                        <span class="text-slate-700 leading-relaxed">
                            {{ $addr['name'] ?? '' }}<br>
                            {{ $addr['full_address'] ?? ($addr['address_detail'] ?? '') }}<br>
                            {{ $addr['district_info'][2]['address_name'] ?? '' }}
                            {{ $addr['district_info'][1]['address_name'] ?? '' }}
                            {{ $addr['district_info'][0]['address_name'] ?? '' }}
                            {{ $addr['postal_code'] ?? '' }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Timeline</h2>
            </div>
            <div class="px-5 py-4 space-y-2.5 text-xs">
                @if($order->tiktok_create_time)
                    <div class="flex justify-between"><span class="text-slate-500">Dibuat</span><span class="text-slate-700">{{ $order->created_at_tiktok?->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->paid_at)
                    <div class="flex justify-between"><span class="text-slate-500">Dibayar</span><span class="text-green-700">{{ $order->paid_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->shipped_at)
                    <div class="flex justify-between"><span class="text-slate-500">Dikirim</span><span class="text-blue-700">{{ $order->shipped_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->delivered_at)
                    <div class="flex justify-between"><span class="text-slate-500">Terkirim</span><span class="text-emerald-700">{{ $order->delivered_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->completed_at)
                    <div class="flex justify-between"><span class="text-slate-500">Selesai</span><span class="text-green-700 font-semibold">{{ $order->completed_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->cancelled_at)
                    <div class="flex justify-between"><span class="text-slate-500">Dibatalkan</span><span class="text-red-700">{{ $order->cancelled_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($order->cancel_reason)
                    <div class="mt-2 rounded-lg bg-red-50 p-2 text-red-600 text-[10px]">{{ $order->cancel_reason }}</div>
                @endif
            </div>
        </div>

        {{-- Account Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5">
                <h2 class="text-sm font-semibold text-slate-700">Akun</h2>
            </div>
            <div class="px-5 py-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Toko</span><span class="font-medium text-slate-900">{{ $order->account?->shop_name ?: '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Platform</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">{{ $order->platform }}</span>
                </div>
            </div>
        </div>

        {{-- POS Integration Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-3.5 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Sinkron ke POS</h2>
                @if($order->is_synced_to_pos)
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-[10px] font-semibold text-green-700">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Tersinkron
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-700">Belum Sync</span>
                @endif
            </div>
            <div class="px-5 py-4">
                @if($order->is_synced_to_pos)
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">SO ID (POS)</span>
                            <span class="font-mono font-semibold text-slate-900">{{ $order->pos_order_id ?: '-' }}</span>
                        </div>
                        @if($order->synced_to_pos_at)
                            <div class="flex justify-between">
                                <span class="text-slate-500">Waktu Sync</span>
                                <span class="text-xs text-slate-700">{{ $order->synced_to_pos_at->format('d M Y H:i') }}</span>
                            </div>
                        @endif
                        <div class="mt-1 flex items-center gap-2 rounded-xl bg-green-50 px-3 py-2.5 text-xs text-green-700">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Data order sudah masuk ke database POS.
                        </div>
                    </div>
                @elseif(in_array($order->order_status, ['UNPAID', 'CANCELLED']))
                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-3 text-xs text-slate-500">
                        <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Order <strong class="text-slate-700">{{ $order->status_label }}</strong> tidak dapat di-push ke POS.
                    </div>
                @else
                    <p class="mb-4 text-xs text-slate-500 leading-relaxed">
                        Klik tombol di bawah untuk mengirim data order ini ke database POS sebagai <strong>Sales Order (SO)</strong>.
                        Item dicocokkan berdasarkan <strong>Seller SKU</strong> yang sesuai di POS.
                    </p>
                    @if(!$order->account?->id_outlet)
                        <div class="mb-3 flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-xs text-amber-700">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Akun belum punya ID Outlet. Atur di <a href="{{ route('integrations.show', $order->account) }}" class="font-semibold underline">halaman Integrasi</a>.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('orders.push-pos', $order) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Push order ini ke POS?\n\nItem dicocokkan berdasarkan Seller SKU.\nPastikan akun sudah memiliki ID Outlet.')"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                @if(!$order->account?->id_outlet) disabled @endif>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            Push ke POS Sekarang
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
