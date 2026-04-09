@extends('layouts.app')
@section('title', 'Profil Saya')
@section('breadcrumb', 'Profil — Akun Saya')

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="font-headline text-2xl font-bold text-primary">Profil Saya</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Kelola informasi akun dan keamanan login Anda.</p>
    </div>

    {{-- AVATAR CARD --}}
    <div class="mb-6 flex items-center gap-5 rounded-2xl bg-surface-container-lowest p-6 shadow-whisper">
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-xl font-bold text-white shadow-md
            {{ $user->isSuperAdmin() || $user->isAdmin() ? 'primary-gradient' : 'bg-surface-container-highest text-on-surface' }}">
            {{ $user->initials }}
        </div>
        <div>
            <h2 class="text-lg font-bold text-on-surface">{{ $user->name }}</h2>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->role_color }}">
                    {{ $user->role_label }}
                </span>
                @if($user->is_active)
                    <span class="inline-flex items-center gap-1 text-xs text-secondary">
                        <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Aktif
                    </span>
                @endif
                @if($user->last_login_at)
                    <span class="text-xs text-on-surface-variant">
                        · Login terakhir {{ $user->last_login_at->format('d M Y H:i') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- INFORMASI PRIBADI --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
            <h3 class="text-sm font-bold text-on-surface">Informasi Pribadi</h3>
        </div>

        @if(session('success'))
        <div class="mx-6 mt-4 flex items-center gap-3 rounded-xl border border-secondary-container bg-secondary-container/30 p-3 text-sm text-on-secondary-container">
            <span class="material-symbols-outlined text-[18px] shrink-0 text-secondary">check_circle</span>
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5 p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nama Lengkap <span class="text-error">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('name') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Email <span class="text-error">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('email') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nomor Telepon <span class="text-sm font-normal text-on-surface-variant">(opsional — bisa dipakai untuk login)</span>
                </label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('phone') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end border-t border-outline-variant/20 pt-4">
                <button type="submit"
                        class="primary-gradient flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Profil
                </button>
            </div>
        </form>
    </div>

    {{-- GANTI PASSWORD --}}
    <div class="mt-6 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper" x-data="{ show1: false, show2: false, show3: false }">
        <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
            <h3 class="text-sm font-bold text-on-surface">Keamanan — Ganti Password</h3>
        </div>

        @if(session('success_password'))
        <div class="mx-6 mt-4 flex items-center gap-3 rounded-xl border border-secondary-container bg-secondary-container/30 p-3 text-sm text-on-secondary-container">
            <span class="material-symbols-outlined text-[18px] shrink-0 text-secondary">check_circle</span>
            {{ session('success_password') }}
        </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-5 p-6">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Password Saat Ini <span class="text-error">*</span>
                </label>
                <div class="relative">
                    <input id="current_password" name="current_password"
                           :type="show1 ? 'text' : 'password'"
                           placeholder="Masukkan password Anda saat ini"
                           class="block w-full rounded-xl border py-2.5 pl-3.5 pr-10 text-sm placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('current_password') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                    <button type="button" @click="show1 = !show1" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]" x-text="show1 ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
                @error('current_password')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new_password" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Password Baru <span class="text-error">*</span>
                </label>
                <div class="relative">
                    <input id="new_password" name="password"
                           :type="show2 ? 'text' : 'password'"
                           placeholder="Min. 8 karakter (huruf & angka)"
                           class="block w-full rounded-xl border py-2.5 pl-3.5 pr-10 text-sm placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('password') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                    <button type="button" @click="show2 = !show2" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]" x-text="show2 ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
                @error('password')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Konfirmasi Password Baru <span class="text-error">*</span>
                </label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation"
                           :type="show3 ? 'text' : 'password'"
                           placeholder="Ulangi password baru"
                           class="block w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2.5 pl-3.5 pr-10 text-sm placeholder-on-surface-variant/40 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10"/>
                    <button type="button" @click="show3 = !show3" class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]" x-text="show3 ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
            </div>

            <div class="flex justify-end border-t border-outline-variant/20 pt-4">
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl bg-secondary px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">lock_reset</span>
                    Ganti Password
                </button>
            </div>
        </form>
    </div>

</div>

@endsection