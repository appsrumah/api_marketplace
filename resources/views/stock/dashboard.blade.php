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

<div x-data="stockSync()" x-cloak>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         HEADER
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Sinkronisasi Stok</h1>
            <p class="mt-1 text-sm text-slate-500">
                Update stok otomatis dari POS ke TikTok &amp; Tokopedia.
                <span class="ml-1 text-xs text-slate-400">Terakhir dibuka: {{ now()->format('d M Y H:i') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Sync Semua --}}
            <button @click="doSyncAll()" :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:from-emerald-600 hover:to-teal-700 disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.98]">
                <svg class="h-4 w-4" :class="loadingAction === 'sync-all' ? 'animate-spin' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="loadingAction === 'sync-all' ? 'Memproses...' : 'Sync Semua Akun'"></span>
            </button>

            {{-- Proses Queue --}}
            <button @click="doRunQueue()" :disabled="loading"
                    class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-blue-500 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-blue-600 hover:to-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.98]">
                <svg class="h-4 w-4" :class="loadingAction === 'run-queue' ? 'animate-spin' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span x-text="loadingAction === 'run-queue' ? 'Memproses...' : 'Proses Queue'"></span>
            </button>

            {{-- Reload --}}
            <a href="{{ route('stock.dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </a>
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         STATS CARDS
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

        {{-- Siap Sync --}}
        <div class="rounded-2xl border border-emerald-100 bg-linear-to-br from-emerald-50 to-teal-50 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-md">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-900">{{ number_format($totalSiapSync) }}</p>
                    <p class="text-xs font-semibold text-emerald-700">Produk Siap Sync</p>
                    <p class="text-[10px] text-emerald-600">ACTIVATE + SKU POS ada</p>
                </div>
            </div>
        </div>

        {{-- Tanpa SKU --}}
        <div class="rounded-2xl border border-amber-100 bg-linear-to-br from-amber-50 to-yellow-50 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-white shadow-md">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-900">{{ number_format($totalTanpaSku) }}</p>
                    <p class="text-xs font-semibold text-amber-700">Tanpa SKU POS</p>
                    <p class="text-[10px] text-amber-600">Akan di-skip saat sync</p>
                </div>
            </div>
        </div>

        {{-- Jobs Pending --}}
        <div class="rounded-2xl border border-blue-100 bg-linear-to-br from-blue-50 to-indigo-50 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-500 text-white shadow-md">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-900" x-text="jobsRemaining ?? '{{ $jobsPending }}'">{{ $jobsPending }}</p>
                    <p class="text-xs font-semibold text-blue-700">Stok yang sedang di Update</p>
                    <p class="text-[10px] text-blue-600">Menunggu diproses</p>
                </div>
            </div>
        </div>

        {{-- Akun Aktif --}}
        <div class="rounded-2xl border border-violet-100 bg-linear-to-br from-violet-50 to-purple-50 p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-500 text-white shadow-md">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-violet-900">{{ $accounts->count() }}</p>
                    <p class="text-xs font-semibold text-violet-700">Akun Terhubung</p>
                    <p class="text-[10px] text-violet-600">TikTok Shop</p>
                </div>
            </div>
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         OUTPUT PANEL (muncul setelah klik tombol)
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div x-show="loading || result || error" x-transition
         class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- Loading state --}}
        <div x-show="loading" class="flex items-center gap-4 p-6">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100">
                <svg class="h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900" x-text="'Sedang ' + loadingLabel + '...'">Sedang memproses...</p>
                <p class="text-sm text-slate-500">Mohon tunggu, jangan tutup halaman ini.</p>
            </div>
        </div>

        {{-- Error state --}}
        <div x-show="error && !loading" class="p-6">
            <div class="flex items-start gap-4 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-red-800">Terjadi Error</p>
                    <p class="mt-1 text-sm text-red-700" x-text="error"></p>
                </div>
            </div>
        </div>

        {{-- Result: Sync All success --}}
        <div x-show="result && !loading && result.status === 'Jobs dispatched'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-emerald-900">
                        âœ… <span x-text="result.queued"></span> Jobs berhasil masuk ke antrian!
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">Klik <strong>Proses Queue</strong> untuk mulai push stok ke TikTok/Tokopedia.</p>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="result.detail && result.detail.length">
                        <template x-for="d in result.detail" :key="d.account">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 00-2 2v6a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-.01a1 1 0 000-2A2 2 0 0116 5v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                                <span x-text="d.account + ': ' + d.queued + ' jobs'"></span>
                            </span>
                        </template>
                    </div>
                    <div x-show="result.skipped && result.skipped.length" class="mt-2">
                        <p class="text-xs text-amber-700">âš  Dilewati: <span x-text="result.skipped.join(', ')"></span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result: Run Queue success --}}
        <div x-show="result && !loading && result.status === 'selesai'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl border border-blue-200 bg-blue-50 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-blue-900">âš¡ Queue worker selesai berjalan</p>
                    <div class="mt-2 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-800">
                            Exit code: <span x-text="result.exit_code" class="ml-1"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                            Sisa queue: <span x-text="result.jobs_remaining" class="ml-1 font-bold text-blue-700"></span> jobs
                        </span>
                    </div>
                    <div x-show="result.output && result.output !== '(tidak ada output)'" class="mt-3">
                        <p class="mb-1 text-xs font-semibold text-slate-500">Output:</p>
                        <pre class="overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100 leading-relaxed" x-text="result.output"></pre>
                    </div>
                    <div x-show="result.jobs_remaining > 0" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p class="text-xs text-amber-800">
                            âš  Masih ada <strong x-text="result.jobs_remaining"></strong> jobs tersisa. Klik <strong>Proses Queue</strong> lagi,
                            atau biarkan cron yang memproses secara otomatis.
                        </p>
                    </div>
                    <div x-show="result.jobs_remaining === 0" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                        <p class="text-xs text-emerald-800">âœ… Semua jobs selesai diproses! Stok sudah di-update ke TikTok/Tokopedia.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result: Sync Account success --}}
        <div x-show="result && !loading && result.account && result.status === 'Jobs dispatched'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <p class="font-bold text-emerald-900">
                        âœ… <span x-text="result.queued"></span> jobs untuk akun <em x-text="result.account"></em>
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">Klik <strong>Proses Queue</strong> untuk push ke TikTok/Tokopedia.</p>
                </div>
            </div>
        </div>

        {{-- Result: Error state from API --}}
        <div x-show="result && !loading && result.status === 'ERROR'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </div>
                <div>
                    <p class="font-bold text-red-800">âŒ Error dari server</p>
                    <p class="mt-1 text-sm text-red-700" x-text="result.pesan"></p>
                    <p x-show="result.tip" class="mt-1 text-xs text-red-600" x-text="'ðŸ’¡ ' + result.tip"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         AKUN CARDS
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="mt-8">
        <h2 class="text-lg font-bold text-slate-900">Status per Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola dan monitor sync stok per akun TikTok Shop.</p>

        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse($accounts as $account)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">

                {{-- Card Header --}}
                <div class="border-b border-slate-100 bg-linear-to-r from-slate-50 to-white px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow">
                                {{ strtoupper(substr($account->seller_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-bold text-slate-900">{{ $account->seller_name }}</h3>
                                <p class="text-[10px] text-slate-500">Outlet ID: {{ $account->id_outlet ?? 'â€”' }}</p>
                            </div>
                        </div>
                        {{-- Status badges --}}
                        <div class="flex flex-col items-end gap-1">
                            @if($account->token_expired)
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Token Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Token Valid
                                </span>
                            @endif
                            @if(!$account->id_outlet)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                    âš  Outlet belum set
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="px-5 py-4">
                    {{-- Stats row --}}
                    <div class="flex items-center gap-4">
                        <div class="flex-1 text-center rounded-xl bg-emerald-50 py-2.5">
                            <p class="text-xl font-bold text-emerald-700">{{ number_format($account->siap_sync) }}</p>
                            <p class="text-[10px] font-semibold text-emerald-600">Siap Sync</p>
                        </div>
                        <div class="flex-1 text-center rounded-xl bg-amber-50 py-2.5">
                            <p class="text-xl font-bold text-amber-700">{{ number_format($account->tanpa_sku) }}</p>
                            <p class="text-[10px] font-semibold text-amber-600">Tanpa SKU</p>
                        </div>
                    </div>

                    {{-- Last sync --}}
                    <div class="mt-3 flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-[10px] font-medium text-slate-500">Terakhir sync stok</p>
                            @if($account->last_update_stock)
                                <p class="text-xs font-semibold text-slate-800">
                                    {{ $account->last_update_stock->diffForHumans() }}
                                    <span class="font-normal text-slate-500">({{ $account->last_update_stock->format('d M Y H:i') }})</span>
                                </p>
                            @else
                                <p class="text-xs font-semibold text-amber-600">Belum pernah sync</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Footer / Actions --}}
                <div class="flex gap-2 border-t border-slate-100 bg-slate-50/60 px-4 py-3">
                    {{-- Sync akun ini --}}
                    {{-- <button @click="doSyncAccount({{ $account->id }}, '{{ addslashes($account->seller_name) }}')"
                            :disabled="loading || {{ $account->id_outlet ? 'false' : 'true' }}"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync Akun
                    </button> --}}
                    {{-- Cek POS --}}
                    <a href="{{ route('stock.test-pos', $account->id) }}"
                       class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Cek POS
                    </a>
                    {{-- Test push 1 --}}
                    {{-- <a href="{{ route('stock.test-push-one', $account->id) }}" target="_blank"
                       class="flex items-center justify-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-100">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Test Push
                    </a> --}}
                </div>

                {{-- Warning: outlet belum set --}}
                @if(!$account->id_outlet)
                <div class="border-t border-amber-100 bg-amber-50 px-4 py-2">
                    <p class="text-[10px] text-amber-700">
                        âš  <strong>id_outlet belum di-set.</strong>
                        POST ke <code class="font-mono text-[10px]">/stock/{{ $account->id }}/set-outlet</code>
                        dengan <code class="font-mono text-[10px]">{ "id_outlet": X }</code>
                    </p>
                </div>
                @endif

                {{-- Warning: token expired --}}
                @if($account->token_expired)
                <div class="border-t border-red-100 bg-red-50 px-4 py-2">
                    <p class="text-[10px] text-red-700">
                        âŒ <strong>Token expired.</strong>
                        <a href="{{ route('tiktok.connect') }}" class="font-semibold underline">Hubungkan ulang akun</a> di dashboard.
                    </p>
                </div>
                @endif
            </div>
            @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center">
                <p class="text-slate-500">Belum ada akun. <a href="{{ route('tiktok.connect') }}" class="font-semibold text-blue-600">Hubungkan akun TikTok</a> terlebih dahulu.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         PRODUK SIAP SYNC TABLE
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="mt-10">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">
                    Produk Siap Disinkronisasi
                    <span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700">
                        {{ number_format($totalSiapSync) }} total
                    </span>
                </h2>
                <p class="mt-1 text-sm text-slate-500">Produk ACTIVATE dengan SKU POS yang akan di-update stoknya ke marketplace.</p>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($produkSiapSync->isEmpty())
                <div class="flex flex-col items-center p-12 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-500">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-700">Belum ada produk siap sync</h3>
                    <p class="mt-1 text-sm text-slate-500">Pastikan produk berstatus ACTIVATE dan sudah diisi <strong>Seller SKU</strong> di TikTok Seller Center, lalu sync produk dari dashboard.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/80">
                                <th class="px-4 py-3 font-semibold text-slate-600">#</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Produk</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 text-center">Platform</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">SKU POS</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">SKU ID</th>
                                <th class="px-4 py-3 font-semibold text-slate-600 text-center">Stok Marketplace</th>
                                <th class="px-4 py-3 font-semibold text-slate-600">Akun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($produkSiapSync as $produk)
                            <tr class="transition hover:bg-emerald-50/30">
                                <td class="px-4 py-3 text-xs text-slate-400 font-mono">
                                    {{ ($produkSiapSync->currentPage() - 1) * $produkSiapSync->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-xs truncate font-medium text-slate-900">{{ $produk->title }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ $produk->product_id }}</p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($produk->platform === 'TIKTOK')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.11v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.75a8.18 8.18 0 004.76 1.52V6.82a4.83 4.83 0 01-1-.13z"/></svg>
                                            TikTok
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                            <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            Tokopedia
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs text-slate-700">
                                        {{ $produk->seller_sku }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs text-slate-500 truncate block max-w-35" title="{{ $produk->sku_id }}">
                                        {{ $produk->sku_id ?: 'â€”' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(($produk->quantity ?? 0) > 0)
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                            {{ number_format($produk->quantity) }}
                                        </span>
                                    @else
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-lg bg-red-50 px-2.5 py-1 text-xs font-bold text-red-600">
                                            0
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($produk->account)
                                        <span class="text-xs text-blue-600 font-medium">{{ $produk->account->seller_name }}</span>
                                        @if($produk->account->last_update_stock)
                                            <p class="mt-0.5 text-[10px] text-emerald-600">
                                                 {{ $produk->account->last_update_stock->diffForHumans() }}
                                            </p>
                                        @else
                                            <p class="mt-0.5 text-[10px] text-amber-500">Belum pernah sync</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="border-t border-slate-100 px-4 py-4">
                    {{ $produkSiapSync->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         INFO CRON (footer panel)
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-700">
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Setup Cron Otomatis (cPanel)
        </h3>
        <p class="mt-1 text-xs text-slate-500">Pasang 2 cron job ini di cPanel agar stok sync otomatis tanpa perlu klik manual.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-white border border-slate-200 p-3">
                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Cron 1 â€” Dispatch Jobs (tiap 30 menit)</p>
                <code class="mt-1.5 block break-all text-[10px] text-blue-700 leading-relaxed">curl -s "{{ config('app.url') }}/stock/cron-sync-all?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-slate-400">Waktu cPanel: <code>*/30 * * * *</code></p>
            </div>
            <div class="rounded-xl bg-white border border-slate-200 p-3">
                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Cron 2 â€” Proses Queue (tiap menit)</p>
                <code class="mt-1.5 block break-all text-[10px] text-blue-700 leading-relaxed">curl -s "{{ config('app.url') }}/stock/run-queue?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-slate-400">Waktu cPanel: <code>* * * * *</code></p>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js" defer></script>
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

