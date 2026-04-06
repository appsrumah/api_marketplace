@extends('layouts.app')
@section('title', 'Profil Saya')

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- ===== HEADER ===== --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Profil Saya</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola informasi akun dan keamanan login Anda.</p>
    </div>

    {{-- ===== AVATAR CARD ===== --}}
    <div class="mb-6 flex items-center gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl
            {{ $user->isSuperAdmin() ? 'bg-linear-to-br from-violet-500 to-purple-600' : ($user->isAdmin() ? 'bg-linear-to-br from-cyan-500 to-blue-600' : 'bg-linear-to-br from-slate-400 to-slate-500') }}
            text-xl font-bold text-white shadow-md">
            {{ $user->initials }}
        </div>
        <div>
            <h2 class="text-lg font-bold text-slate-900">{{ $user->name }}</h2>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->role_color }}">
                    {{ $user->role_label }}
                </span>
                @if($user->is_active)
                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                    </span>
                @endif
                @if($user->last_login_at)
                    <span class="text-xs text-slate-400">
                        · Login terakhir {{ $user->last_login_at->format('d M Y H:i') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== EDIT PROFILE ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
            <h3 class="text-sm font-bold text-slate-700">Informasi Pribadi</h3>
        </div>

        {{-- Success flash --}}
        @if(session('success'))
        <div class="mx-6 mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('name') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Email <span class="text-red-500">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('email') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nomor Telepon
                    <span class="text-slate-400 font-normal">(opsional — bisa dipakai untuk login)</span>
                </label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('phone') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-cyan-600 hover:to-blue-700 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Profil
                </button>
            </div>
        </form>
    </div>

    {{-- ===== GANTI PASSWORD ===== --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" x-data="{ show1: false, show2: false, show3: false }">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
            <h3 class="text-sm font-bold text-slate-700">Keamanan — Ganti Password</h3>
        </div>

        {{-- Success flash password --}}
        @if(session('success_password'))
        <div class="mx-6 mt-4 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success_password') }}
        </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Password Saat Ini <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="current_password" name="current_password"
                           :type="show1 ? 'text' : 'password'"
                           placeholder="Masukkan password Anda saat ini"
                           class="block w-full rounded-xl border py-2.5 pl-3.5 pr-10 text-sm placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('current_password') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                    <button type="button" @click="show1 = !show1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show1" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show1" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="new_password" name="password"
                           :type="show2 ? 'text' : 'password'"
                           placeholder="Min. 8 karakter (huruf & angka)"
                           class="block w-full rounded-xl border py-2.5 pl-3.5 pr-10 text-sm placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('password') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                    <button type="button" @click="show2 = !show2" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show2" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show2" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Konfirmasi Password Baru <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation"
                           :type="show3 ? 'text' : 'password'"
                           placeholder="Ulangi password baru"
                           class="block w-full rounded-xl border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm placeholder-slate-400 shadow-sm transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                    <button type="button" @click="show3 = !show3" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show3" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show3" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl bg-linear-to-r from-amber-500 to-orange-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-amber-600 hover:to-orange-600 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Ganti Password
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush

@endsection
