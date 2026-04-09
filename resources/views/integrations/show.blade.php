@extends('layouts.app')
@section('title', 'Detail Akun â€” ' . ($account->shop_name ?? $account->seller_name))
@section('breadcrumb', 'Integrasi â€” Detail Akun')

@section('content')
<div class="space-y-8">

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         BACK + BREADCRUMB
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('integrations.index') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-1.5 font-medium text-on-surface-variant transition hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Pusat Integrasi
        </a>
        <span class="material-symbols-outlined text-[16px] text-on-surface-variant/40">chevron_right</span>
        <span class="font-medium text-on-surface">{{ $account->shop_name ?? $account->seller_name }}</span>
    </div>

    @php
        $isActive  = $account->status === 'active';
        $isExpired = $account->isTokenExpired();
        $channelColor = $account->channel->color ?? '#6b7280';
    @endphp

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         ACCOUNT HEADER CARD
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper"
         style="border-top: 4px solid {{ $channelColor }};">
        <div class="p-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    {{-- Channel Icon --}}
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                         style="background-color: {{ $channelColor }}20;">
                        <span class="text-xl font-bold" style="color: {{ $channelColor }};">
                            {{ substr($account->channel->name ?? 'MP', 0, 2) }}
                        </span>
                    </div>
                    <div>
                        <h1 class="font-headline text-xl font-bold text-primary">{{ $account->shop_name ?? $account->seller_name }}</h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background-color: {{ $channelColor }}15; color: {{ $channelColor }};">
                                {{ $account->channel->name ?? 'Unknown' }}
                            </span>
                            @if($isActive)
                                @if($isExpired)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2.5 py-0.5 text-xs font-semibold text-on-tertiary-fixed-variant">
                                        <span class="material-symbols-outlined text-[12px]">warning</span>
                                        Token Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2.5 py-0.5 text-xs font-semibold text-on-secondary-container">
                                        <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span>
                                        Terhubung
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-full bg-surface-container px-2.5 py-0.5 text-xs font-semibold text-on-surface-variant">
                                    Tidak Aktif
                                </span>
                            @endif
                        </div>
                        @if($account->seller_name && $account->shop_name)
                            <p class="mt-1 text-sm text-on-surface-variant">Seller: {{ $account->seller_name }}</p>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2">
                    @if($isActive && $isExpired)
                    <form action="{{ route('integrations.refresh-token', $account) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-tertiary-fixed px-4 py-2.5 text-sm font-semibold text-on-tertiary-fixed-variant transition hover:opacity-90">
                            <span class="material-symbols-outlined text-[18px]">refresh</span>
                            Refresh Token
                        </button>
                    </form>
                    @endif

                    @if($isActive)
                    <form action="{{ route('integrations.disconnect', $account) }}" method="POST"
                          onsubmit="return false;"
                          x-data
                          @submit.prevent="Swal.fire({
                              title: 'Putuskan Akun?',
                              text: 'Akun {{ $account->shop_name ?? $account->seller_name }} akan dinonaktifkan.',
                              icon: 'warning',
                              showCancelButton: true,
                              confirmButtonColor: '#ba1a1a',
                              confirmButtonText: 'Ya, Putuskan',
                              cancelButtonText: 'Batal'
                          }).then(r => { if(r.isConfirmed) $el.submit(); })">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-error-container/40 px-4 py-2.5 text-sm font-semibold text-on-error-container transition hover:bg-error-container/60">
                            <span class="material-symbols-outlined text-[18px]">link_off</span>
                            Putuskan
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         INFO CARDS
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Informasi Akun --}}
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Informasi Akun</h3>
            </div>
            <dl class="divide-y divide-outline-variant/10 px-5 text-sm">
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">ID Akun</dt>
                    <dd class="font-mono text-on-surface">{{ $account->id }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Shop ID</dt>
                    <dd class="font-mono text-on-surface">{{ $account->shop_id ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Open ID</dt>
                    <dd class="max-w-48 truncate font-mono text-xs text-on-surface" title="{{ $account->open_id }}">{{ $account->open_id ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Shop Cipher</dt>
                    <dd class="text-on-surface">{{ $account->shop_cipher ? 'âœ… Tersedia' : 'âŒ Belum ada' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Outlet POS</dt>
                    <dd class="text-on-surface">{{ $account->id_outlet ?? 'Belum di-set' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Terhubung Oleh</dt>
                    <dd class="text-on-surface">{{ $account->user->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between py-2.5">
                    <dt class="text-on-surface-variant">Terhubung Sejak</dt>
                    <dd class="text-on-surface">{{ $account->created_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Statistik Produk + Token Status --}}
        <div class="space-y-8">
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Statistik</h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl bg-primary-fixed p-4 text-center">
                            <p class="font-headline text-2xl font-bold text-primary">{{ $productCount }}</p>
                            <p class="text-xs font-medium text-on-surface-variant">Total Produk</p>
                        </div>
                        <div class="rounded-xl bg-secondary-container/40 p-4 text-center">
                            <p class="font-headline text-2xl font-bold text-on-secondary-container">{{ $activeProducts }}</p>
                            <p class="text-xs font-medium text-on-secondary-container/70">Produk Aktif</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('products.index', ['account_id' => $account->id]) }}"
                           class="flex-1 rounded-xl bg-primary-fixed px-4 py-2.5 text-center text-sm font-semibold text-primary transition hover:bg-primary-fixed/80">
                            Lihat Produk
                        </a>
                        <a href="{{ route('orders.index', ['account_id' => $account->id]) }}"
                           class="flex-1 rounded-xl bg-surface-container px-4 py-2.5 text-center text-sm font-semibold text-on-surface transition hover:bg-surface-container-high">
                            Lihat Pesanan
                        </a>
                    </div>
                </div>
            </div>

            {{-- Token Status --}}
            <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
                <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status Token</h3>
                </div>
                <dl class="divide-y divide-outline-variant/10 px-5 text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-on-surface-variant">Access Token</dt>
                        <dd>
                            @if($account->isTokenExpired())
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-container/40 px-2 py-0.5 text-xs font-semibold text-on-error-container">
                                    âŒ Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-xs font-semibold text-on-secondary-container">
                                    âœ… Valid
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-on-surface-variant">Access Token Expire</dt>
                        <dd class="text-on-surface">{{ $account->access_token_expire_in ? \Carbon\Carbon::parse($account->access_token_expire_in)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-on-surface-variant">Refresh Token</dt>
                        <dd>
                            @if($account->isRefreshTokenExpired())
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-container/40 px-2 py-0.5 text-xs font-semibold text-on-error-container">
                                    âŒ Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-xs font-semibold text-on-secondary-container">
                                    âœ… Valid
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-on-surface-variant">Refresh Token Expire</dt>
                        <dd class="text-on-surface">{{ $account->refresh_token_expire_in ? \Carbon\Carbon::parse($account->refresh_token_expire_in)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-on-surface-variant">Token Diperoleh</dt>
                        <dd class="text-on-surface">{{ $account->token_obtained_at ? \Carbon\Carbon::parse($account->token_obtained_at)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         PENGATURAN AKUN
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        <div class="border-b border-outline-variant/20 bg-surface-container-low px-5 py-3.5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pengaturan Akun</h3>
        </div>
        <div class="p-6">
            <form action="{{ route('integrations.update', $account) }}" method="POST" class="max-w-lg space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1 block text-sm font-semibold text-on-surface">ID Outlet POS</label>
                    <input type="number"
                           name="id_outlet"
                           value="{{ old('id_outlet', $account->id_outlet) }}"
                           class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-2.5 text-sm text-on-surface shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/10 focus:outline-none"
                           placeholder="Masukkan ID Outlet dari POS">
                    <p class="mt-1 text-xs text-on-surface-variant">ID Outlet di sistem POS untuk sinkronisasi stok.</p>
                </div>

                @if(\Illuminate\Support\Facades\Schema::hasTable('warehouses'))
                <div>
                    <label class="mb-1 block text-sm font-semibold text-on-surface">Warehouse</label>
                    <select name="warehouse_id"
                            class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-2.5 text-sm text-on-surface shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/10 focus:outline-none">
                        <option value="">â€” Tidak ada â€”</option>
                        @foreach(\App\Models\Warehouse::all() as $wh)
                            <option value="{{ $wh->id }}" {{ $account->warehouse_id == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <button type="submit"
                        class="primary-gradient inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
