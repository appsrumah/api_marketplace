@extends('layouts.app')
@section('title', 'Detail Pesanan #' . Str::limit($order->order_id, 12))

@section('content')

{{-- ===== BACK + TITLE ===== --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali
    </a>
    <div>
        <h1 class="text-xl font-bold text-slate-900">Detail Pesanan</h1>
        <p class="text-xs font-mono text-slate-400">{{ $order->order_id }}</p>
    </div>
    <span class="ml-auto inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $order->status_color }}">
        {{ $order->status_label }}
    </span>
</div>

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
                @if($order->is_synced_to_pos)
                    <div class="flex justify-between"><span class="text-slate-500">POS Sync</span>
                        <span class="inline-flex items-center gap-1 text-green-600 text-xs"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Sinkron</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
