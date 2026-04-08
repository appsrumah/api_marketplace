@extends('layouts.app')
@section('title', 'Pusat Integrasi')

@section('content')
<div class="space-y-8">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                <svg class="mr-2 inline h-7 w-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Pusat Integrasi
            </h1>
            <p class="mt-1 text-sm text-slate-500">Kelola koneksi akun marketplace Anda. Hubungkan toko dari berbagai platform.</p>
        </div>
    </div>

    {{-- ===== STATS CARDS ===== --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Channel Tersedia</p>
            <p class="mt-1.5 text-2xl font-bold text-slate-800">{{ $stats['total_channels'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Akun Terhubung</p>
            <p class="mt-1.5 text-2xl font-bold text-blue-600">{{ $stats['total_accounts'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Aktif</p>
            <p class="mt-1.5 text-2xl font-bold text-emerald-600">{{ $stats['active_accounts'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Token Expired</p>
            <p class="mt-1.5 text-2xl font-bold {{ $stats['expired_tokens'] > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $stats['expired_tokens'] }}</p>
        </div>
    </div>

    {{-- ===== MARKETPLACE CHANNELS ===== --}}
    <div class="space-y-6">
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
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {{-- Channel Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                 style="border-left: 4px solid {{ $channelColor }};">
                <div class="flex items-center gap-4">
                    {{-- Channel Icon --}}
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl shadow-sm"
                         style="background-color: {{ $channelColor }}10;">
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
                        <h3 class="text-lg font-bold text-slate-800">{{ $channel->name }}</h3>
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span>{{ $activeCount }} akun aktif</span>
                            @if(!$isSupported)
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
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
                            class="inline-flex items-center gap-2 rounded-xl border border-transparent px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 active:scale-95"
                            style="background-color: {{ $channelColor }};">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Hubungkan Akun
                    </button>
                </form>
                @else
                <button disabled
                        class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Segera Hadir
                </button>
                @endif
            </div>

            {{-- Connected Accounts --}}
            @if($loopAccounts->isNotEmpty())
            <div class="divide-y divide-slate-100">
                @foreach($loopAccounts as $account)
                @php
                    $isActive  = $account->status === 'active';
                    $isExpired = $account->isTokenExpired();
                @endphp
                <div class="flex items-center justify-between px-6 py-4 transition hover:bg-slate-50">
                    <div class="flex items-center gap-4">
                        {{-- Status indicator --}}
                        <div class="relative flex h-10 w-10 items-center justify-center rounded-xl
                            {{ $isActive ? ($isExpired ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') : 'bg-slate-100 text-slate-400' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            @if($isActive && !$isExpired)
                                <span class="absolute -right-0.5 -top-0.5 flex h-3 w-3">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
                                </span>
                            @endif
                        </div>

                        <div>
                            <h4 class="font-semibold text-slate-800">
                                {{ $account->shop_name ?? $account->seller_name ?? 'Akun #'.$account->id }}
                            </h4>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                @if($account->seller_name && $account->shop_name)
                                    <span>{{ $account->seller_name }}</span>
                                    <span class="text-slate-300">•</span>
                                @endif
                                {{-- Status badge --}}
                                @if($isActive)
                                    @if($isExpired)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                            Token Expired
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Terhubung
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                                        Tidak Aktif
                                    </span>
                                @endif

                                @if($account->user)
                                    <span class="text-slate-300">•</span>
                                    <span>Oleh: {{ $account->user->name }}</span>
                                @endif

                                @if($channelCode === 'TIKTOK')
                                    <span class="text-slate-300">•</span>
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
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                                        title="Refresh Token">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Refresh
                                </button>
                            </form>
                            <form action="{{ route('shopee.disconnect', $account) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-100"
                                        title="Putuskan Koneksi"
                                        onclick="return confirm('Putuskan koneksi toko Shopee ini?')">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Putuskan
                                </button>
                            </form>
                        @else
                            {{-- TikTok: Refresh jika expired + Detail --}}
                            @if($isActive && $isExpired)
                                <form action="{{ route('integrations.refresh-token', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"
                                            title="Refresh Token">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Refresh
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('integrations.show', $account) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                               title="Detail">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Detail
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="px-6 py-8 text-center text-sm text-slate-400">
                @if($isSupported)
                    Belum ada akun terhubung. Klik <strong>"Hubungkan Akun"</strong> untuk memulai.
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
