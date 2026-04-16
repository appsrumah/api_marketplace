@extends('layouts.app')

@section('title', 'Log Sync Stok')
@section('breadcrumb', 'Stok — Log Sync Stok')

@section('content')
<div x-data="syncLogApp()" x-cloak>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock.dashboard') }}"
               class="inline-flex items-center justify-center rounded-xl bg-surface-container p-2 text-on-surface-variant transition hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="font-headline text-xl font-bold text-primary">Log Sync Stok</h1>
                <p class="mt-0.5 text-xs text-on-surface-variant">
                    Riwayat update stok dari POS ke marketplace. Produk gagal bisa di-retry manual.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- Retry All Failed Today --}}
            <button @click="doRetryAllFailed()"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl bg-error/10 px-4 py-2 text-sm font-semibold text-error transition hover:bg-error/20 active:scale-[0.98] disabled:opacity-50">
                <span class="material-symbols-outlined text-[18px]">replay</span>
                <span x-text="loading && loadingAction === 'bulk' ? 'Retrying...' : 'Retry Semua Gagal Hari Ini'"></span>
            </button>
            {{-- Clear Old --}}
            <button @click="doClearOld()"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl bg-surface-container px-4 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high disabled:opacity-50">
                <span class="material-symbols-outlined text-[18px]">delete_sweep</span>
                Hapus Log &gt;30 Hari
            </button>
            {{-- Refresh --}}
            <a href="{{ route('stock.sync-logs') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-surface-container px-4 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                Refresh
            </a>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-2xl bg-surface-container-lowest p-4 text-center shadow-whisper">
            <p class="text-2xl font-bold text-on-surface">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-xs font-medium text-on-surface-variant">Total Log</p>
        </div>
        <div class="rounded-2xl bg-secondary-container/40 p-4 text-center shadow-whisper">
            <p class="text-2xl font-bold text-secondary">{{ number_format($stats['success']) }}</p>
            <p class="mt-1 text-xs font-medium text-on-secondary-container/70">Berhasil</p>
        </div>
        <div class="rounded-2xl bg-error-container/30 p-4 text-center shadow-whisper">
            <p class="text-2xl font-bold text-error">{{ number_format($stats['failed']) }}</p>
            <p class="mt-1 text-xs font-medium text-error/70">Gagal</p>
        </div>
        <div class="rounded-2xl bg-primary-fixed p-4 text-center shadow-whisper">
            <p class="text-2xl font-bold text-primary">{{ number_format($stats['today']) }}</p>
            <p class="mt-1 text-xs font-medium text-primary/70">Hari Ini</p>
        </div>
        <div class="rounded-2xl bg-tertiary-fixed/60 p-4 text-center shadow-whisper">
            <p class="text-2xl font-bold text-on-tertiary-fixed-variant">{{ number_format($stats['today_failed']) }}</p>
            <p class="mt-1 text-xs font-medium text-on-tertiary-fixed-variant/70">Gagal Hari Ini</p>
        </div>
    </div>

    {{-- NOTIFICATION BANNER --}}
    <div x-show="result" x-transition class="mb-4">
        <div :class="result && result.status === 'success' || result && result.status === 'completed'
                     ? 'bg-secondary-container/30 border-secondary/30'
                     : 'bg-error-container/30 border-error/30'"
             class="flex items-start gap-3 rounded-xl border p-4">
            <span class="material-symbols-outlined mt-0.5 shrink-0 text-[20px]"
                  :class="result && (result.status === 'success' || result.status === 'completed') ? 'text-secondary' : 'text-error'"
                  x-text="result && (result.status === 'success' || result.status === 'completed') ? 'check_circle' : 'error'"></span>
            <div class="flex-1 text-sm" x-text="result ? result.message : ''"></div>
            <button @click="result = null" class="shrink-0 text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('stock.sync-logs') }}"
          class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl bg-surface-container-lowest p-4 shadow-whisper">
        {{-- Search --}}
        <div class="min-w-48 flex-1">
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Cari SKU / Produk</label>
            <div class="relative">
                <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-on-surface-variant">search</span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="SKU, nama produk, product_id..."
                       class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2 pl-10 pr-4 text-sm text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/30">
            </div>
        </div>
        {{-- Platform --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Platform</label>
            <select name="platform"
                    class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="">Semua</option>
                <option value="SHOPEE" {{ request('platform') === 'SHOPEE' ? 'selected' : '' }}>Shopee</option>
                <option value="TIKTOK" {{ request('platform') === 'TIKTOK' ? 'selected' : '' }}>TikTok</option>
                <option value="TOKOPEDIA" {{ request('platform') === 'TOKOPEDIA' ? 'selected' : '' }}>Tokopedia</option>
            </select>
        </div>
        {{-- Status --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Status</label>
            <select name="status"
                    class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="">Semua</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>✅ Berhasil</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Gagal</option>
            </select>
        </div>
        {{-- Account --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Akun</label>
            <select name="account_id"
                    class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
                <option value="">Semua Akun</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->account_id }}" {{ request('account_id') == $acc->account_id ? 'selected' : '' }}>
                        {{ $acc->account_name }} ({{ $acc->platform }})
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Date Range --}}
        <div>
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-on-surface-variant">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:border-primary focus:outline-none">
        </div>
        {{-- Buttons --}}
        <div class="flex gap-2">
            <button type="submit"
                    class="primary-gradient inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90">
                <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                Filter
            </button>
            <a href="{{ route('stock.sync-logs') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-high">
                Reset
            </a>
        </div>
    </form>

    {{-- BULK SELECT BAR --}}
    <div x-show="selectedIds.length > 0" x-transition
         class="mb-4 flex items-center gap-4 rounded-xl bg-primary-fixed/60 px-5 py-3">
        <span class="text-sm font-semibold text-primary" x-text="selectedIds.length + ' item dipilih'"></span>
        <button @click="doRetrySelected()"
                :disabled="loading"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
            <span class="material-symbols-outlined text-[14px]">replay</span>
            Retry Terpilih
        </button>
        <button @click="doDeleteSelected()"
                :disabled="loading"
                class="inline-flex items-center gap-1.5 rounded-lg bg-error px-3 py-1.5 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50">
            <span class="material-symbols-outlined text-[14px]">delete</span>
            Hapus Terpilih
        </button>
        <button @click="selectedIds = []"
                class="text-xs font-medium text-primary/70 hover:text-primary">
            Batal
        </button>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-outline-variant/20 bg-surface-container-low">
                    <tr>
                        <th class="px-3 py-3 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded">
                        </th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Waktu</th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Platform</th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Akun</th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Produk</th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">SKU</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Stok Lama</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Stok POS</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Pushed</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Status</th>
                        <th class="px-3 py-3 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Error</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($logs as $log)
                    <tr class="transition hover:bg-surface-container/30 {{ $log->status === 'failed' ? 'bg-error-container/5' : '' }}">
                        {{-- Checkbox --}}
                        <td class="px-3 py-2.5 text-center">
                            @if($log->status === 'failed')
                            <input type="checkbox"
                                   value="{{ $log->id }}"
                                   @change="toggleSelect({{ $log->id }})"
                                   :checked="selectedIds.includes({{ $log->id }})"
                                   class="rounded">
                            @endif
                        </td>
                        {{-- Waktu --}}
                        <td class="whitespace-nowrap px-3 py-2.5 text-xs text-on-surface-variant">
                            {{ $log->synced_at?->format('d/m H:i') }}
                            @if($log->retry_count > 0)
                                <span class="ml-1 rounded bg-tertiary-fixed/60 px-1 py-0.5 text-[10px] font-bold text-on-tertiary-fixed-variant">
                                    R{{ $log->retry_count }}
                                </span>
                            @endif
                        </td>
                        {{-- Platform --}}
                        <td class="px-3 py-2.5">
                            @php
                                $pColor = match($log->platform) {
                                    'SHOPEE' => 'bg-orange-100 text-orange-700',
                                    'TIKTOK' => 'bg-sky-100 text-sky-700',
                                    'TOKOPEDIA' => 'bg-green-100 text-green-700',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $pColor }}">
                                {{ $log->platform }}
                            </span>
                        </td>
                        {{-- Akun --}}
                        <td class="max-w-32 truncate px-3 py-2.5 text-xs text-on-surface">
                            {{ $log->account_name }}
                        </td>
                        {{-- Produk --}}
                        <td class="max-w-48 px-3 py-2.5">
                            <p class="truncate text-xs font-medium text-on-surface" title="{{ $log->title }}">
                                {{ \Illuminate\Support\Str::limit($log->title, 40) }}
                            </p>
                            <p class="mt-0.5 font-mono text-[10px] text-on-surface-variant">
                                {{ $log->product_id }}
                            </p>
                        </td>
                        {{-- SKU --}}
                        <td class="px-3 py-2.5">
                            <span class="font-mono text-xs text-on-surface">{{ $log->seller_sku }}</span>
                        </td>
                        {{-- Stok Lama --}}
                        <td class="px-3 py-2.5 text-center font-mono text-xs text-on-surface-variant">
                            {{ $log->old_quantity }}
                        </td>
                        {{-- Stok POS --}}
                        <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-primary">
                            {{ $log->pos_stock }}
                        </td>
                        {{-- Pushed --}}
                        <td class="px-3 py-2.5 text-center">
                            @if($log->status === 'success')
                                <span class="font-mono text-xs font-bold text-secondary">{{ $log->pushed_stock }}</span>
                            @else
                                <span class="font-mono text-xs text-error">—</span>
                            @endif
                        </td>
                        {{-- Status --}}
                        <td class="px-3 py-2.5 text-center">
                            @if($log->status === 'success')
                                <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container/40 px-2 py-0.5 text-[10px] font-bold text-secondary">
                                    <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                    OK
                                </span>
                            @elseif($log->status === 'failed')
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-container/40 px-2 py-0.5 text-[10px] font-bold text-error">
                                    <span class="material-symbols-outlined text-[12px]">error</span>
                                    GAGAL
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2 py-0.5 text-[10px] font-bold text-on-surface-variant">
                                    {{ strtoupper($log->status) }}
                                </span>
                            @endif
                        </td>
                        {{-- Error --}}
                        <td class="max-w-56 px-3 py-2.5">
                            @if($log->error_message)
                                <p class="truncate text-[11px] text-error" title="{{ $log->error_message }}">
                                    {{ \Illuminate\Support\Str::limit($log->error_message, 60) }}
                                </p>
                            @else
                                <span class="text-[11px] text-on-surface-variant/40">—</span>
                            @endif
                        </td>
                        {{-- Aksi --}}
                        <td class="px-3 py-2.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($log->status === 'failed')
                                    <button @click="doPushOne({{ $log->id }})"
                                            :disabled="loading && loadingId === {{ $log->id }}"
                                            class="inline-flex items-center gap-1 rounded-lg bg-primary/10 px-2.5 py-1.5 text-[11px] font-semibold text-primary transition hover:bg-primary/20 disabled:opacity-50"
                                            title="Retry push stok untuk produk ini">
                                        <span class="material-symbols-outlined text-[14px]"
                                              x-text="loading && loadingId === {{ $log->id }} ? 'hourglass_top' : 'replay'"></span>
                                        <span x-text="loading && loadingId === {{ $log->id }} ? '...' : 'Retry'"></span>
                                    </button>
                                @else
                                    <span class="text-[11px] text-on-surface-variant/30">—</span>
                                @endif

                                {{-- Delete single log --}}
                                <button @click="doDeleteOne({{ $log->id }})"
                                        :disabled="loading && loadingId === {{ $log->id }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-error/10 px-2.5 py-1.5 text-[11px] font-semibold text-error transition hover:bg-error/20 disabled:opacity-50"
                                        title="Hapus log ini">
                                    <span class="material-symbols-outlined text-[14px]"
                                          x-text="loading && loadingId === {{ $log->id }} ? 'hourglass_top' : 'delete'"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-5 py-12 text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-surface-container">
                                <span class="material-symbols-outlined text-[40px] text-on-surface-variant">inventory_2</span>
                            </div>
                            <h3 class="mt-4 text-base font-semibold text-on-surface">Belum ada log</h3>
                            <p class="mt-1 text-sm text-on-surface-variant">
                                Log akan muncul setelah job sync stok berjalan.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- PAGINATION --}}
    @if($logs->hasPages())
    <div class="mt-4">
        {{ $logs->links() }}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function syncLogApp() {
    return {
        loading: false,
        loadingAction: '',
        loadingId: null,
        result: null,
        selectedIds: [],

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx > -1) {
                this.selectedIds.splice(idx, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedIds = @json($logs->where('status', 'failed')->pluck('id')->values());
            } else {
                this.selectedIds = [];
            }
        },

        async doPushOne(logId) {
            if (this.loading) return;
            this.loading = true;
            this.loadingId = logId;
            this.loadingAction = 'push-one';
            this.result = null;

            try {
                const res = await fetch(`/stock/logs/${logId}/push-one`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.result = await res.json();
                if (this.result.status === 'success') {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
                this.loadingId = null;
            }
        },

        async doDeleteOne(logId) {
            if (this.loading) return;
            if (!confirm('Hapus log ini?')) return;

            this.loading = true;
            this.loadingId = logId;
            this.loadingAction = 'delete-one';
            this.result = null;

            try {
                const res = await fetch(`/stock/logs/${logId}/delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.result = await res.json();
                if (this.result.status === 'success') {
                    setTimeout(() => location.reload(), 800);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
                this.loadingId = null;
            }
        },

        async doDeleteSelected() {
            if (this.loading || this.selectedIds.length === 0) return;
            if (!confirm(`Hapus ${this.selectedIds.length} item yang dipilih?`)) return;

            this.loading = true;
            this.loadingAction = 'delete-bulk';
            this.result = null;

            try {
                const res = await fetch('/stock/logs/delete-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ log_ids: this.selectedIds }),
                });
                this.result = await res.json();
                if (this.result.status === 'success' || this.result.status === 'completed') {
                    this.selectedIds = [];
                    setTimeout(() => location.reload(), 900);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
            }
        },

        async doRetrySelected() {
            if (this.loading || this.selectedIds.length === 0) return;
            if (!confirm(`Retry ${this.selectedIds.length} item yang dipilih?`)) return;

            this.loading = true;
            this.loadingAction = 'bulk';
            this.result = null;

            try {
                const res = await fetch('/stock/logs/push-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ log_ids: this.selectedIds }),
                });
                this.result = await res.json();
                if (this.result.status === 'completed') {
                    this.selectedIds = [];
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
            }
        },

        async doRetryAllFailed() {
            if (this.loading) return;
            if (!confirm('Retry semua produk yang GAGAL hari ini?')) return;

            this.loading = true;
            this.loadingAction = 'bulk';
            this.result = null;

            try {
                const res = await fetch('/stock/logs/push-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });
                this.result = await res.json();
                if (this.result.status === 'completed') {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
            }
        },

        async doClearOld() {
            if (this.loading) return;
            if (!confirm('Hapus semua log yang lebih dari 30 hari?')) return;

            this.loading = true;
            this.loadingAction = 'clear';
            this.result = null;

            try {
                const res = await fetch('/stock/logs/clear-old', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.result = await res.json();
                if (this.result.status === 'success') {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (e) {
                this.result = { status: 'error', message: 'Network error: ' + e.message };
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
