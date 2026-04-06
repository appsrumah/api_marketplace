@extends('layouts.app')
@section('title', 'Pesanan')

@section('content')

{{-- ===== HEADER ===== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pesanan</h1>
        <p class="mt-1 text-sm text-slate-500">Semua pesanan dari akun marketplace yang terhubung.</p>
    </div>
    {{-- Sync Button --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" @click.away="open = false"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Sinkron Pesanan
            <svg class="h-4 w-4" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition class="absolute right-0 z-10 mt-2 w-56 rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="py-1">
                @foreach($accounts as $acc)
                    <form method="POST" action="{{ route('orders.sync', $acc->id) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            {{ $acc->shop_name ?: $acc->seller_name }}
                        </button>
                    </form>
                @endforeach
                @if($accounts->isEmpty())
                    <p class="px-4 py-3 text-xs text-slate-400">Belum ada akun terhubung</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ===== STATS BAR ===== --}}
<div class="mt-6 flex flex-wrap items-center gap-3">
    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
        Total: {{ number_format($stats['total']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Siap Kirim: {{ number_format($stats['awaiting_shipment']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700">
        <span class="h-1.5 w-1.5 rounded-full bg-purple-500"></span> Dalam Pengiriman: {{ number_format($stats['in_transit']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">
        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Selesai: {{ number_format($stats['completed']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">
        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Batal: {{ number_format($stats['cancelled']) }}
    </span>
</div>

{{-- ===== FILTER BAR ===== --}}
<form method="GET" action="{{ route('orders.index') }}" class="mt-6">
    <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        {{-- Search --}}
        <div class="flex-1 min-w-50">
            <label class="mb-1 block text-xs font-medium text-slate-500">Cari Pesanan</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order ID, nama pembeli, resi..."
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
        </div>

        {{-- Status --}}
        <div class="w-52">
            <label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
            <select name="status"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="ALL" {{ request('status', 'ALL') === 'ALL' ? 'selected' : '' }}>Semua Status</option>
                @foreach(\App\Models\Order::STATUS_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Account --}}
        <div class="w-48">
            <label class="mb-1 block text-xs font-medium text-slate-500">Akun</label>
            <select name="account_id"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Semua Akun</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->shop_name ?: $acc->seller_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date From --}}
        <div class="w-40">
            <label class="mb-1 block text-xs font-medium text-slate-500">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>

        {{-- Date To --}}
        <div class="w-40">
            <label class="mb-1 block text-xs font-medium text-slate-500">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>

        {{-- Submit --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'account_id', 'date_from', 'date_to']))
                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
            @endif
        </div>
    </div>
</form>

{{-- ===== ORDERS TABLE ===== --}}
<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    @if($orders->isEmpty())
        <div class="flex flex-col items-center p-12 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada pesanan</h3>
            <p class="mt-1 text-sm text-slate-500">Klik "Sinkron Pesanan" untuk menarik data dari TikTok.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="px-4 py-3 font-semibold text-slate-600">#</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Order ID</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Pembeli</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Toko</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Items</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Total</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Status</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Pengiriman</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Tanggal</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($orders as $order)
                        <tr class="transition hover:bg-blue-50/40">
                            <td class="px-4 py-3 text-xs text-slate-400 font-mono">
                                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('orders.show', $order) }}" class="font-mono text-xs text-blue-600 hover:underline">
                                    {{ \Illuminate\Support\Str::limit($order->order_id, 18) }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $order->buyer_name ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-slate-500">{{ $order->account?->shop_name ?: $order->account?->seller_name ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-6 items-center justify-center rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">
                                    {{ $order->items_count ?? $order->items->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-slate-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->tracking_number)
                                    <p class="font-mono text-xs text-slate-600">{{ $order->tracking_number }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $order->shipping_provider }}</p>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($order->tiktok_create_time)
                                    <span class="text-xs text-slate-500">{{ $order->created_at_tiktok?->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-xs text-slate-400">{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('orders.show', $order) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-blue-50 hover:text-blue-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-slate-100 px-4 py-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection
