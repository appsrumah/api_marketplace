@extends('layouts.app')
@section('title', 'Detail Akun — ' . ($account->shop_name ?? $account->seller_name))

@section('content')
<div class="space-y-8">

    {{-- ===== BREADCRUMB + BACK ===== --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('integrations.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <span class="text-sm text-slate-400">Pusat Integrasi</span>
        <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-sm font-medium text-slate-700">{{ $account->shop_name ?? $account->seller_name }}</span>
    </div>

    @php
        $isActive  = $account->status === 'active';
        $isExpired = $account->isTokenExpired();
        $channelColor = $account->channel->color ?? '#6b7280';
    @endphp

    {{-- ===== ACCOUNT HEADER ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
         style="border-top: 4px solid {{ $channelColor }};">
        <div class="p-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    {{-- Channel Icon --}}
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-sm"
                         style="background-color: {{ $channelColor }}20;">
                        <span class="text-xl font-bold" style="color: {{ $channelColor }};">
                            {{ substr($account->channel->name ?? 'MP', 0, 2) }}
                        </span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ $account->shop_name ?? $account->seller_name }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background-color: {{ $channelColor }}15; color: {{ $channelColor }};">
                                {{ $account->channel->name ?? 'Unknown' }}
                            </span>
                            @if($isActive)
                                @if($isExpired)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                        Token Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Terhubung
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                                    Tidak Aktif
                                </span>
                            @endif
                        </div>
                        @if($account->seller_name && $account->shop_name)
                            <p class="mt-1 text-sm text-slate-500">Seller: {{ $account->seller_name }}</p>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2">
                    @if($isActive && $isExpired)
                    <form action="{{ route('integrations.refresh-token', $account) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
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
                              confirmButtonColor: '#ef4444',
                              confirmButtonText: 'Ya, Putuskan',
                              cancelButtonText: 'Batal'
                          }).then(r => { if(r.isConfirmed) $el.submit(); })">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Putuskan
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== INFO CARDS ===== --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Informasi Akun --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Informasi Akun</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">ID Akun</dt>
                    <dd class="font-mono text-slate-700">{{ $account->id }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">Shop ID</dt>
                    <dd class="font-mono text-slate-700">{{ $account->shop_id ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">Open ID</dt>
                    <dd class="max-w-48 truncate font-mono text-xs text-slate-700" title="{{ $account->open_id }}">{{ $account->open_id ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">Shop Cipher</dt>
                    <dd class="text-slate-700">{{ $account->shop_cipher ? '✅ Tersedia' : '❌ Belum ada' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">Outlet POS</dt>
                    <dd class="text-slate-700">{{ $account->id_outlet ?? 'Belum di-set' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-50 pb-2">
                    <dt class="font-medium text-slate-500">Terhubung Oleh</dt>
                    <dd class="text-slate-700">{{ $account->user->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-slate-500">Terhubung Sejak</dt>
                    <dd class="text-slate-700">{{ $account->created_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Statistik Produk --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Statistik</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl bg-blue-50 p-4 text-center">
                        <p class="text-2xl font-bold text-blue-700">{{ $productCount }}</p>
                        <p class="text-xs font-medium text-blue-500">Total Produk</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 text-center">
                        <p class="text-2xl font-bold text-emerald-700">{{ $activeProducts }}</p>
                        <p class="text-xs font-medium text-emerald-500">Produk Aktif</p>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <a href="{{ route('products.index', ['account_id' => $account->id]) }}"
                       class="flex-1 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-center text-sm font-semibold text-blue-700 transition hover:bg-blue-100">
                        Lihat Produk
                    </a>
                    <a href="{{ route('orders.index', ['account_id' => $account->id]) }}"
                       class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Lihat Pesanan
                    </a>
                </div>
            </div>

            {{-- Token Status --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Status Token</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="font-medium text-slate-500">Access Token</dt>
                        <dd>
                            @if($account->isTokenExpired())
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    ❌ Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    ✅ Valid
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="font-medium text-slate-500">Access Token Expire</dt>
                        <dd class="text-slate-700">{{ $account->access_token_expire_in ? \Carbon\Carbon::parse($account->access_token_expire_in)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="font-medium text-slate-500">Refresh Token</dt>
                        <dd>
                            @if($account->isRefreshTokenExpired())
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    ❌ Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                    ✅ Valid
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-slate-50 pb-2">
                        <dt class="font-medium text-slate-500">Refresh Token Expire</dt>
                        <dd class="text-slate-700">{{ $account->refresh_token_expire_in ? \Carbon\Carbon::parse($account->refresh_token_expire_in)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="font-medium text-slate-500">Token Diperoleh</dt>
                        <dd class="text-slate-700">{{ $account->token_obtained_at ? \Carbon\Carbon::parse($account->token_obtained_at)->format('d M Y H:i') : '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    {{-- ===== PENGATURAN AKUN ===== --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">Pengaturan Akun</h3>

        <form action="{{ route('integrations.update', $account) }}" method="POST" class="max-w-lg space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">ID Outlet POS</label>
                <input type="number"
                       name="id_outlet"
                       value="{{ old('id_outlet', $account->id_outlet) }}"
                       class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                       placeholder="Masukkan ID Outlet dari POS">
                <p class="mt-1 text-xs text-slate-400">ID Outlet di sistem POS untuk sinkronisasi stok.</p>
            </div>

            @if(\Illuminate\Support\Facades\Schema::hasTable('warehouses'))
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Warehouse</label>
                <select name="warehouse_id"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm shadow-sm transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
                    <option value="">— Tidak ada —</option>
                    @foreach(\App\Models\Warehouse::all() as $wh)
                        <option value="{{ $wh->id }}" {{ $account->warehouse_id == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Pengaturan
            </button>
        </form>
    </div>

</div>
@endsection
