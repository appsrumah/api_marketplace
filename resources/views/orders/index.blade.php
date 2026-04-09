@extends('layouts.app')
@section('title', 'Pesanan')
@section('breadcrumb', 'Pesanan — Manajemen Order')

@section('content')

{{-- ===== HEADER ===== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Marketplace</p>
        <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Pesanan</h1>
        <p class="mt-1.5 text-sm text-on-surface-variant">Semua pesanan dari akun marketplace yang terhubung.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        {{-- Push ke POS Button --}}
        @if($stats['unsynced_pos'] > 0)
            <form method="POST" action="{{ route('orders.push-all-pos') }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Push {{ $stats['unsynced_pos'] }} order yang belum sync ke POS?\n(Maks 50 order per sekali push)')"
                        class="inline-flex items-center gap-2 rounded-xl bg-secondary px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                    Push ke POS ({{ $stats['unsynced_pos'] }})
                </button>
            </form>
        @endif
        {{-- Sync Dropdown --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false"
                    class="primary-gradient inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined text-[18px]">sync</span>
                Sinkron Pesanan
                <span class="material-symbols-outlined text-[16px] transition-transform" :class="open ? 'rotate-180' : ''">expand_more</span>
            </button>
            <div x-show="open" x-cloak x-transition
                 class="absolute right-0 z-10 mt-2 w-56 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper ring-1 ring-outline-variant/30">
                <div class="py-1.5">
                    @foreach($accounts as $acc)
                        <form method="POST" action="{{ route('orders.sync', $acc->id) }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-on-surface transition hover:bg-surface-container-low">
                                <span class="h-2 w-2 rounded-full bg-secondary"></span>
                                {{ $acc->shop_name ?: $acc->seller_name }}
                            </button>
                        </form>
                    @endforeach
                    @if($accounts->isEmpty())
                        <p class="px-4 py-3 text-xs text-on-surface-variant">Belum ada akun terhubung</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== STATS BAR ===== --}}
<div class="mt-6 flex flex-wrap items-center gap-2.5">
    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface">
        Total: {{ number_format($stats['total']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-fixed px-3 py-1.5 text-xs font-semibold text-primary">
        <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> Siap Kirim: {{ number_format($stats['awaiting_shipment']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1.5 text-xs font-semibold text-on-surface">
        <span class="h-1.5 w-1.5 rounded-full bg-on-surface-variant"></span> Dalam Pengiriman: {{ number_format($stats['in_transit']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
        <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Selesai: {{ number_format($stats['completed']) }}
    </span>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-error-container px-3 py-1.5 text-xs font-semibold text-on-error-container">
        <span class="h-1.5 w-1.5 rounded-full bg-error"></span> Batal: {{ number_format($stats['cancelled']) }}
    </span>
    @if($stats['unsynced_pos'] > 0)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-tertiary-fixed px-3 py-1.5 text-xs font-semibold text-on-tertiary-fixed-variant">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span> Belum Sync POS: {{ number_format($stats['unsynced_pos']) }}
        </span>
    @endif
</div>

{{-- ===== FILTER BAR ===== --}}
<form method="GET" action="{{ route('orders.index') }}" class="mt-6">
    <div class="flex flex-wrap items-end gap-3 rounded-2xl bg-surface-container-lowest p-4 shadow-whisper">
        {{-- Search --}}
        <div class="min-w-50 flex-1">
            <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Cari Pesanan</label>
            <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant/50">search</span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Order ID, nama pembeli, resi..."
                       class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2.5 pl-10 pr-4 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
            </div>
        </div>

        {{-- Status --}}
        <div class="w-52">
            <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Status</label>
            <select name="status"
                    class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                <option value="ALL" {{ request('status', 'ALL') === 'ALL' ? 'selected' : '' }}>Semua Status</option>
                @foreach(\App\Models\Order::STATUS_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Account --}}
        <div class="w-48">
            <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Akun</label>
            <select name="account_id"
                    class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
                <option value="">Semua Akun</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->shop_name ?: $acc->seller_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date From --}}
        <div class="w-40">
            <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Dari Tanggal</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
        </div>

        {{-- Date To --}}
        <div class="w-40">
            <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Sampai Tanggal</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2.5 text-sm text-on-surface transition focus:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/10">
        </div>

        {{-- Submit --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="primary-gradient inline-flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-95">
                <span class="material-symbols-outlined text-[18px]">filter_list</span>
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'account_id', 'date_from', 'date_to']))
                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container">
                    Reset
                </a>
            @endif
        </div>
    </div>
</form>

{{-- ===== ORDERS TABLE ===== --}}
<div class="mt-6 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
    @if($orders->isEmpty())
        <div class="flex flex-col items-center p-12 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-fixed">
                <span class="material-symbols-outlined text-[32px] text-primary">receipt_long</span>
            </div>
            <h3 class="mt-4 text-base font-semibold text-on-surface">Belum ada pesanan</h3>
            <p class="mt-1 text-sm text-on-surface-variant">Klik "Sinkron Pesanan" untuk menarik data dari TikTok.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-outline-variant/20 bg-surface-container-low">
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">#</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Order ID</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pembeli</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Toko</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Items</th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-on-surface-variant">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pengiriman</th>
                        <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tanggal</th>
                        <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @foreach($orders as $order)
                        <tr class="transition hover:bg-surface-container-low">
                            <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">
                                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('orders.show', $order) }}" class="font-mono text-xs font-semibold text-primary hover:underline">
                                    {{ \Illuminate\Support\Str::limit($order->order_id, 18) }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-on-surface">{{ $order->buyer_name ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-on-surface-variant">{{ $order->account?->shop_name ?: $order->account?->seller_name ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex min-w-6 items-center justify-center rounded-lg bg-surface-container px-2 py-0.5 text-xs font-bold text-on-surface">
                                    {{ $order->items_count ?? $order->items->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-on-surface">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $order->status_color }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($order->tracking_number)
                                    <p class="font-mono text-xs text-on-surface">{{ $order->tracking_number }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $order->shipping_provider }}</p>
                                @else
                                    <span class="text-xs text-on-surface-variant/50">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($order->tiktok_create_time)
                                    <span class="text-xs text-on-surface-variant">{{ $order->created_at_tiktok?->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-xs text-on-surface-variant/60">{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-primary-fixed px-2.5 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-fixed/70">
                                        <span class="material-symbols-outlined text-[14px]">visibility</span>
                                        Detail
                                    </a>
                                    @if($order->is_synced_to_pos)
                                        <span title="Sudah di-sync ke POS (SO: {{ $order->pos_order_id }})"
                                              class="inline-flex cursor-default items-center rounded-lg bg-secondary-container p-1.5 text-on-secondary-container">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                        </span>
                                    @elseif(!in_array($order->order_status, ['UNPAID', 'CANCELLED']))
                                        <form method="POST" action="{{ route('orders.push-pos', $order) }}">
                                            @csrf
                                            <button type="submit" title="Push ke POS"
                                                    class="inline-flex items-center rounded-lg bg-secondary-container/40 p-1.5 text-secondary transition hover:bg-secondary-container">
                                                <span class="material-symbols-outlined text-[14px]">cloud_upload</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="border-t border-outline-variant/20 px-4 py-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection
