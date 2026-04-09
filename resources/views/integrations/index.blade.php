@extends('layouts.app')
@section('title', 'Pusat Integrasi')
@section('breadcrumb', 'Integrasi Ã¢â‚¬â€ Pusat Koneksi Marketplace')

@section('content')
<div class="space-y-8">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-secondary">Marketplace</p>
            <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Pusat Integrasi</h1>
            <p class="mt-1.5 text-sm text-on-surface-variant">Kelola koneksi akun marketplace Anda. Hubungkan toko dari berbagai platform.</p>
        </div>
    </div>

    {{-- ===== STATS CARDS ===== --}}
    <div class="grid grid-cols-2 gap-5 sm:grid-cols-4">
        <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Channel Tersedia</p>
            <p class="mt-1.5 font-headline text-2xl font-extrabold text-on-surface">{{ $stats['total_channels'] }}</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Akun Terhubung</p>
            <p class="mt-1.5 font-headline text-2xl font-extrabold text-primary">{{ $stats['total_accounts'] }}</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Aktif</p>
            <p class="mt-1.5 font-headline text-2xl font-extrabold text-secondary">{{ $stats['active_accounts'] }}</p>
        </div>
        <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Token Expired</p>
            <p class="mt-1.5 font-headline text-2xl font-extrabold {{ $stats['expired_tokens'] > 0 ? 'text-error' : 'text-on-surface-variant/40' }}">{{ $stats['expired_tokens'] }}</p>
        </div>
    </div>

    {{-- ===== MARKETPLACE CHANNELS ===== --}}
    <div class="space-y-8">
        @foreach($channels as $channel)
        @php
            $channelCode   = strtoupper($channel->identifier);
            $channelColor  = $channel->color ?? '#6b7280';
            // TikTok pakai AccountShopTiktok (via $accountsByChannel)
            // Shopee & channel lain pakai ChannelAccount (via $channelAccounts dari controller)
            $loopAccounts  = ($channelCode === 'TIKTOK')
                ? $accountsByChannel->get($channel->id, collect())
                : $channelAccounts->where('channel_id', $channel->id);
            $activeCount   = $loopAccounts->where('status', 'active')->count();
            $supportedPlatforms = ['TIKTOK', 'SHOPEE'];
            $isSupported   = in_array($channelCode, $supportedPlatforms);
        @endphp
        <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
            {{-- Channel Header --}}
            <div class="flex items-center justify-between px-6 py-4"
                 style="border-left: 4px solid {{ $channelColor }}; background: linear-gradient(90deg, {{ $channelColor }}08 0%, transparent 60%);">
                <div class="flex items-center gap-4">
                    {{-- Channel Icon --}}
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl shadow-sm"
                         style="background-color: {{ $channelColor }}18;">
                        @switch($channelCode)
                            @case('TIKTOK')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="{{ $channelColor }}"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.88-2.88 2.89 2.89 0 012.88-2.88c.28 0 .56.04.82.11v-3.51a6.37 6.37 0 00-.82-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V8.83a8.28 8.28 0 004.84 1.56V6.94a4.84 4.84 0 01-1.08-.25z"/></svg>
                            @break
                            @case('SHOPEE')
                                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="{{ $channelColor }}"><path d="M12 2C8.74 2 6.45 4.57 6.07 5.88H4.5a2 2 0 00-2 2v10.25a3.87 3.87 0 003.87 3.87h11.26a3.87 3.87 0 003.87-3.87V7.88a2 2 0 00-2-2h-1.57C17.55 4.57 15.26 2 12 2zm0 1.5c2.2 0 3.75 1.73 4.07 2.38H7.93C8.25 5.23 9.8 3.5 12 3.5zm0 5a3.5 3.5 0 110 7 3.5 3.5 0 010-7zm0 1.5a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            @break
                            @default
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg text-xs font-bold text-white" style="background-color: {{ $channelColor }};">
                                    {{ substr($channel->name, 0, 2) }}
                                </div>
                        @endswitch
                    </div>

                    <div>
                        <h3 class="font-headline text-lg font-bold text-on-surface">{{ $channel->name }}</h3>
                        <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                            <span>{{ $activeCount }} akun aktif</span>
                            @if(!$isSupported)
                                <span class="inline-flex items-center rounded-full bg-tertiary-fixed px-2 py-0.5 text-[10px] font-semibold text-on-tertiary-fixed-variant">
                                    Segera Hadir
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Connect Button --}}
                @if($isSupported)
                <form action="{{ route('integrations.connect', $channel) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 active:scale-95"
                            style="background-color: {{ $channelColor }};">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Hubungkan Akun
                    </button>
                </form>
                @else
                <button disabled
                        class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-surface-container-high px-5 py-2.5 text-sm font-semibold text-on-surface-variant/50">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                    Segera Hadir
                </button>
                @endif
            </div>

            {{-- Divider --}}
            <div class="h-px bg-outline-variant/20"></div>

            {{-- Connected Accounts --}}
            @if($loopAccounts->isNotEmpty())
            <div class="divide-y divide-outline-variant/10">
                @foreach($loopAccounts as $account)
                @php
                    $isActive  = $account->status === 'active';
                    $isExpired = $account->isTokenExpired();
                @endphp
                <div class="flex items-center justify-between px-6 py-4 transition hover:bg-surface-container-low">
                    <div class="flex items-center gap-4">
                        {{-- Status indicator --}}
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-xl
                            {{ $isActive ? ($isExpired ? 'bg-tertiary-fixed text-on-tertiary-fixed-variant' : 'bg-secondary-container text-on-secondary-container') : 'bg-surface-container text-on-surface-variant/50' }}">
                            <span class="material-symbols-outlined text-[20px]">storefront</span>
                            @if($isActive && !$isExpired)
                                <span class="absolute -right-0.5 -top-0.5 flex h-3 w-3">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-secondary opacity-75"></span>
                                    <span class="relative inline-flex h-3 w-3 rounded-full bg-secondary"></span>
                                </span>
                            @endif
                        </div>

                        <div>
                            <h4 class="font-semibold text-on-surface">
                                {{ $account->shop_name ?? $account->seller_name ?? 'Akun #'.$account->id }}
                            </h4>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-on-surface-variant">
                                @if($account->seller_name && $account->shop_name)
                                    <span>{{ $account->seller_name }}</span>
                                    <span class="text-outline-variant">Ã¢â‚¬Â¢</span>
                                @endif
                                {{-- Status badge --}}
                                @if($isActive)
                                    @if($isExpired)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-tertiary-fixed px-2 py-0.5 text-[10px] font-semibold text-on-tertiary-fixed-variant">
                                            <span class="material-symbols-outlined text-[12px]">warning</span>
                                            Token Expired
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary-container px-2 py-0.5 text-[10px] font-semibold text-on-secondary-container">
                                            <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span>
                                            Terhubung
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center rounded-full bg-surface-container-high px-2 py-0.5 text-[10px] font-semibold text-on-surface-variant">
                                        Tidak Aktif
                                    </span>
                                @endif

                                @if($account->user)
                                    <span class="text-outline-variant">Ã¢â‚¬Â¢</span>
                                    <span>Oleh: {{ $account->user->name }}</span>
                                @endif

                                @if($channelCode === 'TIKTOK')
                                    <span class="text-outline-variant">Ã¢â‚¬Â¢</span>
                                    <span>{{ $account->produk_count ?? $account->produk()->count() }} produk</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($channelCode === 'SHOPEE')
                            {{-- Shopee: Refresh Token + Disconnect --}}
                            <form action="{{ route('shopee.refresh-token', $account) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-fixed px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-fixed/70"
                                        title="Refresh Token">
                                    <span class="material-symbols-outlined text-[14px]">refresh</span>
                                    Refresh
                                </button>
                            </form>
                            <form action="{{ route('shopee.disconnect', $account) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-error-container/40 px-3 py-1.5 text-xs font-semibold text-error transition hover:bg-error-container"
                                        title="Putuskan Koneksi"
                                        onclick="return confirm('Putuskan koneksi toko Shopee ini?')">
                                    <span class="material-symbols-outlined text-[14px]">link_off</span>
                                    Putuskan
                                </button>
                            </form>
                        @else
                            {{-- TikTok: Refresh jika expired + Detail --}}
                            @if($isActive && $isExpired)
                                <form action="{{ route('integrations.refresh-token', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-tertiary-fixed px-3 py-1.5 text-xs font-semibold text-on-tertiary-fixed-variant transition hover:opacity-80"
                                            title="Refresh Token">
                                        <span class="material-symbols-outlined text-[14px]">refresh</span>
                                        Refresh
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('integrations.show', $account) }}"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface transition hover:bg-surface-container-high"
                               title="Detail">
                                <span class="material-symbols-outlined text-[14px]">settings</span>
                                Detail
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-6 py-8 text-center text-sm text-on-surface-variant">
                @if($isSupported)
                    Belum ada akun terhubung. Klik <strong class="text-primary">"Hubungkan Akun"</strong> untuk memulai.
                @else
                    Integrasi {{ $channel->name }} akan segera tersedia.
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

</div>
@endsection
