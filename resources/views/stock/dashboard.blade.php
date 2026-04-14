@extends('layouts.app')
@section('title', 'Sinkronisasi Stok')
@section('breadcrumb', 'Stok - Sinkronisasi Otomatis')

@section('content')
<div x-data="stockSync()" x-init="init()" @destroy="destroy()" x-cloak>

    {{-- HEADER --}}
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

            {{-- Lepas Stuck Jobs --}}
            <button @click="doRunQueue()" :disabled="loading"
                    class="primary-gradient inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]" :class="loadingAction === 'run-queue' ? 'animate-spin' : ''">restart_alt</span>
                <span x-text="loadingAction === 'run-queue' ? 'Memeriksa...' : 'Lepas & Retry'"></span>
            </button>

            {{-- Reload --}}
            <a href="{{ route('stock.dashboard') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container-high">
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                Refresh
            </a>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="mt-6 grid grid-cols-2 gap-5 lg:grid-cols-4">

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
                    <p class="text-xs font-semibold text-on-surface">Toko yang Sedang Update Stoknya</p>
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


    {{-- ═══════════════════════════════════════════════════════════════
         LIVE MONITOR — Progress sync stok real-time (polling 5 detik)
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="mt-6 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-outline-variant/20 bg-surface-container-low px-5 py-3">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                          :class="liveStatus && liveStatus.queue.total > 0 ? 'bg-primary' : 'bg-secondary'"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full"
                          :class="liveStatus && liveStatus.queue.total > 0 ? 'bg-primary' : 'bg-secondary'"></span>
                </span>
                <h3 class="text-sm font-bold text-on-surface">Live Monitor Sync Stok</h3>
                <span class="rounded-full bg-surface-container px-2 py-0.5 text-[10px] text-on-surface-variant">Auto-refresh 5 detik</span>
            </div>
            <span class="text-[10px] text-on-surface-variant/60"
                  x-text="liveStatus ? 'Dicek: ' + new Date(liveStatus.checked_at).toLocaleTimeString('id-ID') : 'Memuat...'"></span>
        </div>
        {{-- Queue summary --}}
        <div class="grid grid-cols-4 divide-x divide-outline-variant/20 border-b border-outline-variant/20">
            <div class="px-5 py-3 text-center">
                <p class="text-xs font-medium text-on-surface-variant">Antri (siap)</p>
                <p class="mt-0.5 text-xl font-bold"
                   :class="liveStatus && liveStatus.queue.available > 0 ? 'text-secondary' : 'text-on-surface-variant/40'"
                   x-text="liveStatus ? liveStatus.queue.available : '—'"></p>
            </div>
            <div class="px-5 py-3 text-center">
                <p class="text-xs font-medium text-on-surface-variant">Menunggu retry</p>
                <p class="mt-0.5 text-xl font-bold"
                   :class="liveStatus && liveStatus.queue.delayed > 0 ? 'text-amber-500' : 'text-on-surface-variant/40'"
                   x-text="liveStatus ? liveStatus.queue.delayed : '—'"></p>
            </div>
            <div class="px-5 py-3 text-center">
                <p class="text-xs font-medium text-on-surface-variant">Sedang jalan</p>
                <p class="mt-0.5 text-xl font-bold"
                   :class="liveStatus && liveStatus.queue.running > 0 ? 'text-primary' : 'text-on-surface-variant/40'"
                   x-text="liveStatus ? liveStatus.queue.running : '—'"></p>
            </div>
            <div class="px-5 py-3 text-center">
                <p class="text-xs font-medium text-on-surface-variant">Gagal total</p>
                <p class="mt-0.5 text-xl font-bold"
                   :class="liveStatus && liveStatus.queue.failed > 0 ? 'text-error' : 'text-on-surface-variant/40'"
                   x-text="liveStatus ? liveStatus.queue.failed : '—'"></p>
            </div>
        </div>
        {{-- Per-akun progress rows --}}
        <div x-show="liveStatus && liveStatus.accounts.length" class="divide-y divide-outline-variant/10">
            <template x-for="acc in (liveStatus ? liveStatus.accounts : [])" :key="acc.account_id">
                <div class="flex items-center gap-4 px-5 py-3">
                    <div class="primary-gradient flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white shadow-sm"
                         x-text="acc.account_name.charAt(0).toUpperCase()"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-on-surface" x-text="acc.account_name"></p>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                  :class="{
                                      'bg-primary/10 text-primary': acc.progress && acc.progress.status === 'running',
                                      'bg-secondary-container text-on-secondary-container': acc.progress && acc.progress.status === 'completed',
                                      'bg-error-container text-on-error-container': acc.progress && acc.progress.status === 'failed',
                                      'bg-tertiary-fixed text-on-tertiary-fixed-variant': acc.progress && acc.progress.status === 'starting',
                                      'bg-surface-container text-on-surface-variant': !acc.progress || acc.progress.status === 'skipped',
                                  }"
                                  x-text="acc.progress ? ({'running':'▶ Sedang jalan','starting':'⏳ Memulai...','completed':'✅ Selesai','failed':'❌ Gagal','skipped':'⚠ Outlet kosong'}[acc.progress.status] || acc.progress.status) : 'Idle'">
                            </span>
                        </div>
                        {{-- Progress bar --}}
                        <template x-if="acc.progress && (acc.progress.status === 'running' || acc.progress.status === 'starting')">
                            <div class="mt-1.5">
                                <div class="flex items-center justify-between text-[10px] text-on-surface-variant">
                                    <span x-text="acc.progress.current + ' / ' + acc.progress.total + ' SKU'"></span>
                                    <span x-text="acc.progress.total > 0 ? Math.round(acc.progress.current / acc.progress.total * 100) + '%' : '0%'"></span>
                                </div>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-surface-container">
                                    <div class="h-full rounded-full bg-primary transition-all duration-500"
                                         :style="'width:' + (acc.progress.total > 0 ? Math.round(acc.progress.current / acc.progress.total * 100) : 0) + '%'"></div>
                                </div>
                                <p class="mt-0.5 text-[10px] text-on-surface-variant/70"
                                   x-text="'✓ ' + acc.progress.success + ' berhasil · ✗ ' + acc.progress.failed + ' gagal'"></p>
                            </div>
                        </template>
                        {{-- Selesai --}}
                        <p x-show="acc.progress && acc.progress.status === 'completed'"
                           class="mt-0.5 text-[10px] text-on-surface-variant"
                           x-text="acc.progress ? '✓ ' + acc.progress.success + ' berhasil · ✗ ' + acc.progress.failed + ' gagal dari ' + acc.progress.total + ' SKU · selesai ' + new Date(acc.progress.finished_at).toLocaleTimeString('id-ID') : ''"></p>
                        {{-- Error --}}
                        <p x-show="acc.progress && acc.progress.status === 'failed'"
                           class="mt-0.5 truncate text-[10px] text-error" x-text="acc.progress ? acc.progress.error : ''"></p>
                        {{-- Idle --}}
                        <p x-show="!acc.progress || !['running','starting','completed','failed'].includes(acc.progress ? acc.progress.status : '')"
                           class="mt-0.5 text-[10px] text-on-surface-variant/60"
                           x-text="acc.last_update_human ? 'Terakhir sync: ' + acc.last_update_human : 'Belum pernah sync'"></p>
                    </div>
                    {{-- Tombol Sync 1 akun --}}
                    <button @click="doSyncAccount(acc.account_id, acc.account_name)"
                            :disabled="loading || (acc.progress && acc.progress.status === 'running')"
                            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface transition hover:bg-surface-container-high disabled:cursor-not-allowed disabled:opacity-50">
                        <span class="material-symbols-outlined text-[14px]"
                              :class="loadingAction === 'sync-account-' + acc.account_id || (acc.progress && acc.progress.status === 'running') ? 'animate-spin' : ''">sync</span>
                        <span x-text="acc.progress && acc.progress.status === 'running' ? 'Jalan...' : 'Sync'"></span>
                    </button>
                </div>
            </template>
        </div>
        {{-- Error panel — muncul jika ada failed jobs --}}
        <div x-show="liveStatus && liveStatus.queue.failed > 0 && liveStatus.recent_errors && liveStatus.recent_errors.length"
             class="border-t border-outline-variant/20 bg-error-container/20 px-5 py-3">
            <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-error">
                <span class="material-symbols-outlined text-[14px]">error</span>
                <span x-text="liveStatus.queue.failed + ' job gagal — ' + liveStatus.recent_errors.length + ' error terakhir:'"></span>
                <button @click="doClearFailed()"
                        class="ml-auto rounded-lg bg-error/10 px-2 py-0.5 text-[10px] font-semibold text-error hover:bg-error/20">
                    Hapus & Dispatch Ulang
                </button>
            </p>
            <template x-for="(err, i) in liveStatus.recent_errors" :key="i">
                <div class="mb-1 rounded-lg bg-surface-container-lowest px-3 py-2">
                    <p class="text-[10px] font-semibold text-on-surface" x-text="err.job + ' — ' + err.failed_at"></p>
                    <p class="mt-0.5 truncate font-mono text-[10px] text-error" x-text="err.error"></p>
                </div>
            </template>
        </div>
        {{-- Loading skeleton --}}
        <div x-show="!liveStatus" class="px-5 py-6 text-center text-sm text-on-surface-variant">
            <svg class="mx-auto mb-2 h-5 w-5 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Memuat status...
        </div>
    </div>
    {{-- OUTPUT PANEL (muncul setelah klik tombol) --}}
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
                        âœ… <span x-text="result.queued"></span> Jobs berhasil masuk ke antrian!
                    </p>
                    <p class="mt-1 text-sm text-on-surface-variant">Jobs masuk ke antrian. Cron worker akan memproses otomatis dalam ~1 menit. Pantau progress di <strong class="text-primary">Live Monitor</strong>.</p>
                    <div class="mt-3 flex flex-wrap gap-2" x-show="result.detail && result.detail.length">
                        <template x-for="d in result.detail" :key="d.account">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-secondary-container px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
                                <span class="material-symbols-outlined text-[12px]">list_alt</span>
                                <span x-text="d.account + ': ' + d.queued + ' jobs'"></span>
                            </span>
                        </template>
                    </div>
                    <div x-show="result.skipped && result.skipped.length" class="mt-2">
                        <p class="text-xs text-on-surface-variant">âš  Dilewati: <span x-text="result.skipped.join(', ')"></span></p>
                    </div>
                </div>
            </div>
        </div>

                {{-- Result: Lepas & Retry (runQueueWeb) --}}
        <div x-show="result && !loading && result.status === 'selesai'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-primary-fixed/60 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-white">
                    <span class="material-symbols-outlined text-[16px]" x-text="result && result.released > 0 ? 'restart_alt' : 'info'"></span>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-on-surface" x-text="result && result.released > 0 ? 'Job stuck berhasil dilepas!' : 'Status Antrian'"></p>
                    <p class="mt-1 text-sm text-on-surface-variant" x-text="result ? result.info : ''"></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-secondary-container px-3 py-1.5 text-xs font-semibold text-on-secondary-container">
                            Siap: <span x-text="result ? result.available : 0" class="ml-1 font-bold"></span>
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-primary-fixed px-3 py-1.5 text-xs font-semibold text-primary">
                            Sedang jalan: <span x-text="result ? result.running : 0" class="ml-1 font-bold"></span>
                        </span>
                    </div>
                    <div x-show="result && result.released > 0" class="mt-3 rounded-xl bg-secondary-container/30 p-3">
                        <p class="text-xs text-on-secondary-container">Pantau progress di <strong>Live Monitor</strong>. Cron worker akan memproses dalam ~1 menit.</p>
                    </div>
                    <div x-show="result && result.jobs_remaining === 0" class="mt-3 rounded-xl bg-surface-container p-3">
                        <p class="text-xs text-on-surface-variant">Antrian kosong. Klik <strong class="text-secondary">Sync Semua Akun</strong> untuk mulai sync stok.</p>
                    </div>
                </div>
            </div>
        </div>

di atas. Cron worker akan memproses dalam ~1 menit.</p>
                    </div>
                    <div x-show="result && result.jobs_remaining === 0" class="mt-3 rounded-xl bg-surface-container p-3">
                        <p class="text-xs text-on-surface-variant">Antrian kosong. Klik <strong class="text-secondary">Sync Semua Akun</strong> untuk mulai sync stok.</p>
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
                    <span x-text="result.queued"></span> jobs untuk akun <em x-text="result.account"></em>
                    </p>
                    <p class="mt-1 text-sm text-on-surface-variant">Job masuk ke antrian. Cron worker memproses otomatis dalam ~1 menit. Pantau di <strong class="text-primary">Live Monitor</strong>.</p>
                </div>
            </div>
        </div>

        {{-- Result: Error state from API --}}
        <div x-show="result && !loading && result.status === 'ERROR'" class="p-6">
            <div class="flex items-start gap-4 rounded-xl bg-error-container/40 p-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-error-container text-on-error-container">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                </div>
                {{-- <div>
                    <p class="font-bold text-on-error-container">âŒ Error dari server</p>
                    <p class="mt-1 text-sm text-on-error-container/80" x-text="result.pesan"></p>
                    <p x-show="result.tip" class="mt-1 text-xs text-on-error-container/60" x-text="'ðŸ’¡ ' + result.tip"></p>
                </div> --}}
            </div>
        </div>
    </div>

    {{-- AKUN CARDS --}}
    <div class="mt-8">
        <h2 class="font-headline text-lg font-bold text-on-surface">Status per Akun</h2>
        <p class="mt-1 text-sm text-on-surface-variant">Kelola dan monitor sync stok per akun TikTok Shop.</p>

        <div class="mt-4 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
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
                                <div class="flex items-center gap-1.5">
                                    @if($account->platform === 'SHOPEE')
                                        <span class="inline-flex rounded bg-orange-500 px-1.5 py-0.5 text-[9px] font-bold text-white">Shopee</span>
                                    @else
                                        <span class="inline-flex rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-white">TikTok</span>
                                    @endif
                                    <span class="text-[10px] text-on-surface-variant">Outlet: {{ $account->id_outlet ?? '—' }}</span>
                                </div>
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
                                    âš  Outlet belum set
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
                    {{-- Cek POS (TikTok only — Shopee uses same POS) --}}
                    @if($account->platform === 'TIKTOK')
                    <a href="{{ route('stock.test-pos', $account->id) }}"
                       class="flex items-center justify-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface transition hover:bg-surface-container-high">
                        <span class="material-symbols-outlined text-[14px]">content_paste_search</span>
                        Cek POS
                    </a>
                    @else
                    <span class="flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-2 text-xs font-semibold text-on-surface-variant">
                        <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                        {{ number_format($account->siap_sync) }} produk siap sync
                    </span>
                    @endif
                </div>

                {{-- Warning: outlet belum set --}}
                @if(!$account->id_outlet)
                <div class="border-t border-outline-variant/20 bg-tertiary-fixed/40 px-4 py-2">
                    <p class="text-[10px] text-on-tertiary-fixed-variant">
                        âš  <strong>id_outlet belum di-set.</strong>
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
                        <a href="{{ route('integrations.index') }}" class="font-semibold underline">Hubungkan ulang akun</a> di halaman Integrasi.
                    </p>
                </div>
                @endif
            </div>
            @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-outline-variant/40 p-12 text-center">
                <p class="text-on-surface-variant">Belum ada akun. <a href="{{ route('integrations.index') }}" class="font-semibold text-primary">Hubungkan akun marketplace</a> terlebih dahulu.</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- PRODUK SIAP SYNC TABLE --}}
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
                                    @elseif($produk->platform === 'SHOPEE')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-orange-500 px-2.5 py-1 text-xs font-semibold text-white">
                                            Shopee
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
                                        {{ $produk->sku_id ?: 'â€”' }}
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
                                    <span class="text-xs font-medium text-primary">{{ $produk->account_name }}</span>
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

    {{--INFO CRON (footer panel) --}}
    {{-- <div class="mt-8 rounded-2xl bg-surface-container-low p-5">
        <h3 class="flex items-center gap-2 text-sm font-bold text-on-surface">
            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">schedule</span>
            Setup Cron Otomatis (cPanel)
        </h3>
        <p class="mt-1 text-xs text-on-surface-variant">Pasang 2 cron job ini di cPanel agar stok sync otomatis tanpa perlu klik manual.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-surface-container-lowest p-3 shadow-whisper">
                <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cron 1 Dispatch Jobs (tiap 30 menit)</p>
                <code class="mt-1.5 block break-all text-[10px] leading-relaxed text-primary">curl -s "{{ config('app.url') }}/stock/cron-sync-all?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-on-surface-variant/60">Waktu cPanel: <code>*/30 * * * *</code></p>
            </div>
            <div class="rounded-xl bg-surface-container-lowest p-3 shadow-whisper">
                <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cron 2 Proses Queue (tiap menit)</p>
                <code class="mt-1.5 block break-all text-[10px] leading-relaxed text-primary">curl -s "{{ config('app.url') }}/stock/run-queue?secret={{ config('app.stock_sync_secret') }}"</code>
                <p class="mt-1 text-[10px] text-on-surface-variant/60">Waktu cPanel: <code>* * * * *</code></p>
            </div>
        </div>
    </div> --}}

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
        liveStatus: null,
        _pollTimer: null,

        init() {
            this.fetchLiveStatus();
            this._pollTimer = setInterval(() => this.fetchLiveStatus(), 5000);
        },

        destroy() {
            if (this._pollTimer) clearInterval(this._pollTimer);
        },

        async fetchLiveStatus() {
            try {
                const res = await fetch('{{ route("stock.sync-progress") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                this.liveStatus = await res.json();
            } catch (e) { /* silent — jangan ganggu UI jika network error */ }
        },

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

        async doClearFailed() {
            if (!confirm('Hapus semua failed jobs dan dispatch ulang 19 jobs baru?\n\nPastikan masalah error sudah diperbaiki sebelum dispatch ulang.')) return;
            await this.call('clear-failed', 'Bersihkan failed & dispatch ulang', () =>
                fetch('{{ route("stock.clear-failed") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                })
            );
            await this.fetchLiveStatus();
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
