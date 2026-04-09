@extends('layouts.app')
@section('title', 'Sinkronisasi Stok')
@section('breadcrumb', 'Stok — Sinkronisasi Otomatis')

@section('content')
<div x-data="stockSync()" x-cloak>

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-secondary">Inventory</p>
            <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Sinkronisasi Stok</h1>
            <p class="mt-1.5 text-sm text-on-surface-variant">
                Update stok otomatis dari POS ke TikTok &amp; Tokopedia.
                <span class="ml-1 text-xs text-on-surface-variant/60">Terakhir dibuka: {{ now()->format('d M Y H:i') }}</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            {{-- Sync Semua --}}
            <button @click="doSyncAll()" :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl bg-secondary px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]" :class="loadingAction === 'sync-all' ? 'animate-spin' : ''">sync</span>
                <span x-text="loadingAction === 'sync-all' ? 'Memproses...' : 'Sync Semua Akun'"></span>
            </button>

            {{-- Proses Queue --}}
            <button @click="doRunQueue()" :disabled="loading"
                    class="primary-gradient inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]" :class="loadingAction === 'run-queue' ? 'animate-spin' : ''">bolt</span>
                <span x-text="loadingAction === 'run-queue' ? 'Memproses...' : 'Proses Queue'"></span>
            </button>

            {{-- Reload --}}
            <a href="{{ route('stock.dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                Refresh
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STATS CARDS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

        {{-- Siap Sync --}}
        <div class="rounded-2xl bg-secondary-container/40 p-5 shadow-whisper">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-secondary text-white shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                </div>
                <div>
                    <p class="font-headline text-2xl font-extrabold text-on-surface">{{ number_format($totalSiapSync) }}</p>
                    <p class="text-xs font-semibold text-on-surface">Produk Siap Sync</p>
                    <p class="text-[10px] text-on-surface-variant">ACTIVATE + SKU POS ada</p>
                </div>
            </div>
        </div>

        {{-- Tanpa SKU --}}
        <div class="rounded-2xl bg-tertiary-fixed/60 p-5 shadow-whisper">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">warning</span>
                </div>
                <div>
                    <p class="font-headline text-2xl font-extrabold text-on-surface">{{ number_format($totalTanpaSku) }}</p>
                    <p class="text-xs font-semibold text-on-surface">Tanpa SKU POS</p>
                    <p class="text-[10px] text-on-surface-variant">Akan di-skip saat sync</p>
                </div>
            </div>
        </div>

        {{-- Jobs Pending --}}
        <div class="rounded-2xl bg-primary-fixed p-5 shadow-whisper">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">inventory</span>
                </div>
                <div>
                    <p class="font-headline text-2xl font-extrabold text-on-surface" x-text="jobsRemaining ?? '{{ $jobsPending }}'">{{ $jobsPending }}</p>
                    <p class="text-xs font-semibold text-on-surface">Stok Sedang Update</p>
                    <p class="text-[10px] text-on-surface-variant">Menunggu diproses</p>
                </div>
            </div>
        </div>

        {{-- Akun Aktif --}}
        <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-container text-white shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">group</span>
                </div>
                <div>
                    <p class="font-headline text-2xl font-extrabold text-on-surface">{{ $accounts->count() }}</p>
                    <p class="text-xs font-semibold text-on-surface">Akun Terhubung</p>
                    <p class="text-[10px] text-on-surface-variant">TikTok Shop</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         OUTPUT PANEL (muncul setelah klik tombol)
    ═══════════════════════════════════════════════════════════════ --}}
    <div x-show="loading || result || error" x-transition
         class="mt-6 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">

        {{-- Loading state --}}
        <div x-show="loading" class="flex items-center gap-4 p-6">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-fixed">
                <svg class="h-5 w-5 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-on-surface" x-text="'Sedang ' + loadingLabel + '...'">Sedang memproses...</p>
                <p class="text-sm text-on-surface-variant">Mohon tunggu, jangan tutup halaman ini.</p>
            </div>
        </div>

        {{-- Error state --}}
        <div x-show="error && !loading" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-error-container/40 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                </div>
                <div>
                    <p class="font-semibold text-on-error-container">Terjadi Error</p>
                    <p class="mt-1 text-sm text-on-error-container/80" x-text="error"></p>
                </div>
            </div>
        </div>

        {{-- Result: Sync All success --}}
        <div x-show="result && !loading && result.status === 'Jobs dispatched'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-secondary-container/30 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary text-white">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-on-surface">
                        ✅ <span x-text="result.queued"></span> Jobs berhasil masuk ke antrian!
                    </p>
                    <p class="mt-1 text-sm text-on-surface-variant">Klik <strong class="text-primary">Proses Queue</strong> untuk mulai push stok ke TikTok/Tokopedia.</p>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="result.detail && result.detail.length">
                        <template x-for="d in result.detail" :key="d.account">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-secondary-container px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
                                <span class="material-symbols-outlined text-[12px]">list_alt</span>
                                <span x-text="d.account + ': ' + d.queued + ' jobs'"></span>
                            </span>
                        </template>
                    </div>
                    <div x-show="result.skipped && result.skipped.length" class="mt-2">
                        <p class="text-xs text-on-surface-variant">⚠ Dilewati: <span x-text="result.skipped.join(', ')"></span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result: Run Queue success --}}
        <div x-show="result && !loading && result.status === 'selesai'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-primary-fixed/60 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-white">
                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-on-surface">⚡ Queue worker selesai berjalan</p>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-primary-fixed px-3 py-1.5 text-xs font-semibold text-primary">
                            Exit code: <span x-text="result.exit_code" class="ml-1"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface">
                            Sisa queue: <span x-text="result.jobs_remaining" class="ml-1 font-bold text-primary"></span> jobs
                        </span>
                    </div>
                    <div x-show="result.output && result.output !== '(tidak ada output)'" class="mt-3">
                        <p class="mb-1 text-xs font-semibold text-on-surface-variant">Output:</p>
                        <pre class="overflow-x-auto rounded-xl bg-on-surface p-3 text-xs leading-relaxed text-surface" x-text="result.output"></pre>
                    </div>
                    <div x-show="result.jobs_remaining > 0" class="mt-3 rounded-xl bg-tertiary-fixed p-3">
                        <p class="text-xs text-on-tertiary-fixed-variant">
                            ⚠ Masih ada <strong x-text="result.jobs_remaining"></strong> jobs tersisa. Klik <strong>Proses Queue</strong> lagi,
                            atau biarkan cron yang memproses secara otomatis.
                        </p>
                    </div>
                    <div x-show="result.jobs_remaining === 0" class="mt-3 rounded-xl bg-secondary-container/30 p-3">
                        <p class="text-xs text-on-secondary-container">✅ Semua jobs selesai diproses! Stok sudah di-update ke TikTok/Tokopedia.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result: Sync Account success --}}
        <div x-show="result && !loading && result.account && result.status === 'Jobs dispatched'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-secondary-container/30 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary text-white">
                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                </div>
                <div>
                    <p class="font-bold text-on-surface">
                        ✅ <span x-text="result.queued"></span> jobs untuk akun <em x-text="result.account"></em>
                    </p>
                    <p class="mt-1 text-sm text-on-surface-variant">Klik <strong class="text-primary">Proses Queue</strong> untuk push ke TikTok/Tokopedia.</p>
                </div>
            </div>
        </div>

        {{-- Result: Error state from API --}}
        <div x-show="result && !loading && result.status === 'ERROR'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-error-container/40 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                </div>
                <div>
                    <p class="font-bold text-on-error-container">❌ Error dari server</p>
                    <p class="mt-1 text-sm text-on-error-container/80" x-text="result.pesan"></p>
                    <p x-show="result.tip" class="mt-1 text-xs text-on-error-container/60" x-text="'💡 ' + result.tip"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         AKUN CARDS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-8">
        <h2 class="font-headline text-lg font-bold text-on-surface">Status per Akun</h2>
        <p class="mt-1 text-sm text-on-surface-variant">Kelola dan monitor sync stok per akun TikTok Shop.</p>

        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($accounts as $account)
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper transition hover:shadow-md">

                {{-- Card Header --}}
                <div class="primary-gradient/5 bg-surface-container-low px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="primary-gradient flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow">
                                {{ strtoupper(substr($account->seller_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-on-surface">{{ $account->seller_name }}</h3>
                                <p class="text-[10px] text-on-surface-variant">Outlet ID: {{ $account->id_outlet ?? '—' }}</p>
                            </div>
                        </div>
                        {{-- Status badges --}}
                        <div class="flex flex-col items-end gap-1">
                            @if($account->token_expired)
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-container px-2 py-0.5 text-[10px] font-semibold text-on-error-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-error"></span> Token Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-[10px] font-semibold text-on-secondary-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Token Valid
                                </span>
                            @endif
                            @if(!$account->id_outlet)
                                <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2 py-0.5 text-[10px] font-semibold text-on-tertiary-fixed-variant">
                                    ⚠ Outlet belum set
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="h-px bg-outline-variant/20"></div>

                {{-- Card Body --}}
                <div class="px-5 py-4">
                    {{-- Stats row --}}
                    <div class="flex items-center gap-4">
                        <div class="flex-1 rounded-xl bg-secondary-container/30 py-2.5 text-center">
                            <p class="font-headline text-xl font-bold text-on-secondary-container">{{ number_format($account->siap_sync) }}</p>
                            <p class="text-[10px] font-semibold text-on-secondary-container/80">Siap Sync</p>
                        </div>
                        <div class="flex-1 rounded-xl bg-tertiary-fixed/60 py-2.5 text-center">
                            <p class="font-headline text-xl font-bold text-on-tertiary-fixed-variant">{{ number_format($account->tanpa_sku) }}</p>
                            <p class="text-[10px] font-semibold text-on-tertiary-fixed-variant/80">Tanpa SKU</p>
                        </div>
                    </div>

                    {{-- Last sync --}}
                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-surface-container-low px-3 py-2">
                        <span class="material-symbols-outlined shrink-0 text-[16px] text-on-surface-variant/50">schedule</span>
                        <div>
                            <p class="text-[10px] font-medium text-on-surface-variant">Terakhir sync stok</p>
                            @if($account->last_update_stock)
                                <p class="text-xs font-semibold text-on-surface">
                                    {{ $account->last_update_stock->diffForHumans() }}
                                    <span class="font-normal text-on-surface-variant">({{ $account->last_update_stock->format('d M Y H:i') }})</span>
                                </p>
                            @else
                                <p class="text-xs font-semibold text-on-surface-variant">Belum pernah sync</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Footer / Actions --}}
                <div class="flex gap-2 border-t border-outline-variant/20 bg-surface-container-low/60 px-4 py-3">
                    {{-- Cek POS --}}
                    <a href="{{ route('stock.test-pos', $account->id) }}"
                       class="flex items-center justify-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-[14px]">content_paste_search</span>
                        Cek POS
                    </a>
                </div>

                {{-- Warning: outlet belum set --}}
                @if(!$account->id_outlet)
                <div class="border-t border-outline-variant/20 bg-tertiary-fixed/40 px-4 py-2">
                    <p class="text-[10px] text-on-tertiary-fixed-variant">
                        ⚠ <strong>id_outlet belum di-set.</strong>
                        POST ke <code class="font-mono text-[10px]">/stock/{{ $account->id }}/set-outlet</code>
                        dengan <code class="font-mono text-[10px]">{ "id_outlet": X }</code>
                    </p>
                </div>
                @endif

                {{-- Warning: token expired --}}
                @if($account->token_expired)
                <div class="border-t border-outline-variant/20 bg-error-container/30 px-4 py-2">
                    <p class="text-[10px] text-on-error-container">
                        ❌ <strong>Token expired.</strong>
                        <a href="{{ route('tiktok.connect') }}" class="font-semibold underline">Hubungkan ulang akun</a> di dashboard.
                    </p>
                </div>
                @endif
            </div>
            @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-outline-variant/40 p-12 text-center">
                <p class="text-on-surface-variant">Belum ada akun. <a href="{{ route('tiktok.connect') }}" class="font-semibold text-primary">Hubungkan akun TikTok</a> terlebih dahulu.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PRODUK SIAP SYNC TABLE
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-10">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-headline text-lg font-bold text-on-surface">
                    Produk Siap Disinkronisasi
                    <span class="ml-2 inline-flex items-center rounded-full bg-secondary-container px-2.5 py-0.5 text-xs font-bold text-on-secondary-container">
                        {{ number_format($totalSiapSync) }} total
                    </span>
                </h2>
                <p class="mt-1 text-sm text-on-surface-variant">Produk ACTIVATE dengan SKU POS yang akan di-update stoknya ke marketplace.</p>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            @if($produkSiapSync->isEmpty())
                <div class="flex flex-col items-center p-12 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-tertiary-fixed">
                        <span class="material-symbols-outlined text-[32px] text-on-tertiary-fixed-variant">warning</span>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-on-surface">Belum ada produk siap sync</h3>
                    <p class="mt-1 text-sm text-on-surface-variant">Pastikan produk berstatus ACTIVATE dan sudah diisi <strong>Seller SKU</strong> di TikTok Seller Center, lalu sync produk dari dashboard.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-outline-variant/20 bg-surface-container-low">
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">#</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Produk</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Platform</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">SKU POS</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">SKU ID</th>
                                <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider text-on-surface-variant">Stok Marketplace</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Akun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($produkSiapSync as $produk)
                            <tr class="transition hover:bg-surface-container-low">
                                <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">
                                    {{ ($produkSiapSync->currentPage() - 1) * $produkSiapSync->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-xs truncate font-medium text-on-surface">{{ $produk->title }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-on-surface-variant/60">{{ $produk->product_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($produk->platform === 'TIKTOK')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-2.5 py-1 text-xs font-semibold text-primary">
                                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
                                            TikTok
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-1 text-xs font-semibold text-on-secondary-container">
                                            Tokopedia
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-lg bg-surface-container px-2.5 py-1 font-mono text-xs text-on-surface">
                                        {{ $produk->seller_sku }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="block max-w-35 truncate font-mono text-xs text-on-surface-variant" title="{{ $produk->sku_id }}">
                                        {{ $produk->sku_id ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(($produk->quantity ?? 0) > 0)
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-secondary-container/50 px-2.5 py-1 text-xs font-bold text-on-secondary-container">
                                            {{ number_format($produk->quantity) }}
                                        </span>
                                    @else
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-error-container/40 px-2.5 py-1 text-xs font-bold text-on-error-container">
                                            0
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($produk->account)
                                        <span class="text-xs font-medium text-primary">{{ $produk->account->seller_name }}</span>
                                        @if($produk->account->last_update_stock)
                                            <p class="mt-0.5 text-[10px] text-secondary">
                                                {{ $produk->account->last_update_stock->diffForHumans() }}
                                            </p>
                                        @else
                                            <p class="mt-0.5 text-[10px] text-on-surface-variant">Belum pernah sync</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="border-t border-outline-variant/20 px-4 py-4">
                    {{ $produkSiapSync->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         INFO CRON (footer panel)
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-8 rounded-2xl bg-surface-container-low p-5">
        <h3 class="flex items-center gap-2 text-sm font-bold text-on-surface">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">schedule</span>
            Setup Cron Otomatis (cPanel)
        </h3>
        <p class="mt-1 text-xs text-on-surface-variant">Pasang 2 cron job ini di cPanel agar stok sync otomatis tanpa perlu klik manual.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-surface-container-lowest p-3 shadow-whisper">
                <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cron 1 — Dispatch Jobs (tiap 30 menit)</p>
                <code class="mt-1.5 block break-all text-[10px] leading-relaxed text-primary">curl -s "{{ config('app.url') }}/stock/cron-sync-all?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-on-surface-variant/60">Waktu cPanel: <code>*/30 * * * *</code></p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-3 shadow-whisper">
                <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cron 2 — Proses Queue (tiap menit)</p>
                <code class="mt-1.5 block break-all text-[10px] leading-relaxed text-primary">curl -s "{{ config('app.url') }}/stock/run-queue?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-on-surface-variant/60">Waktu cPanel: <code>* * * * *</code></p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function stockSync() {
    return {
        loading: false,
        loadingAction: '',
        loadingLabel: '',
        result: null,
        error: null,
        jobsRemaining: null,

        async doSyncAll() {
            await this.call('sync-all', 'Dispatch semua jobs', () =>
                fetch('{{ route("stock.sync-all") }}', {
                    headers: { 'Accept': 'application/json' }
                })
            );
        },

        async doRunQueue() {
            await this.call('run-queue', 'Memproses queue', () =>
                fetch('{{ route("stock.run-queue-web") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                })
            );
            // update jobs count card after queue run
            if (this.result && this.result.jobs_remaining !== undefined) {
                this.jobsRemaining = this.result.jobs_remaining;
            }
        },

        async doSyncAccount(id, name) {
            await this.call('sync-account-' + id, 'Sync akun ' + name, () =>
                fetch('/stock/' + id + '/sync', {
                    headers: { 'Accept': 'application/json' }
                })
            );
        },

        async call(action, label, fetchFn) {
            this.loading = true;
            this.loadingAction = action;
            this.loadingLabel = label;
            this.result = null;
            this.error = null;

            // Scroll ke output panel
            this.$nextTick(() => {
                const panel = document.getElementById('output-panel-anchor');
                if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            try {
                const response = await fetchFn();
                const data = await response.json();
                this.result = data;
                this.result._action = action;
            } catch (e) {
                this.error = 'Network error: ' + e.message;
            } finally {
                this.loading = false;
                this.loadingAction = '';
            }
        }
    };
}
</script>
@endpush
