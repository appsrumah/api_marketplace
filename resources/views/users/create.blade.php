@extends('layouts.app')
@section('title', 'Tambah Pengguna')
@section('breadcrumb', 'Pengguna — Tambah Baru')

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- HEADER --}}
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('users.index') }}"
           class="flex items-center justify-center rounded-xl bg-surface-container p-2 text-on-surface-variant transition hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline text-xl font-bold text-primary">Tambah Pengguna Baru</h1>
            <p class="text-sm text-on-surface-variant">Buat sub-akun dengan hak akses terbatas</p>
        </div>
    </div>

    {{-- FORM --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
            <p class="text-sm font-bold text-on-surface">Data Pengguna</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-5 p-6">
            @csrf

            {{-- Nama --}}
            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nama Lengkap <span class="text-error">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       placeholder="Nama pengguna"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('name') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Email <span class="text-error">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       placeholder="email@contoh.com"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('email') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Telepon --}}
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nomor Telepon <span class="text-sm font-normal text-on-surface-variant">(opsional)</span>
                </label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('phone') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                <p class="mt-1 text-xs text-on-surface-variant">Nomor telepon dapat digunakan untuk login.</p>
                @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Peran --}}
            <div>
                <label class="mb-2 block text-sm font-semibold text-on-surface">
                    Peran / Role <span class="text-error">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['admin' => ['Admin', 'bg-primary-fixed text-primary', 'Kelola marketplace & produk, tidak bisa buat pengguna'], 'operator' => ['Operator', 'bg-surface-container text-on-surface-variant', 'Lihat data & jalankan sinkronisasi stok saja']] as $value => [$label, $color, $desc])
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}"
                               {{ old('role', 'operator') === $value ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="rounded-xl border-2 p-4 transition peer-checked:border-primary peer-checked:bg-primary-fixed/40
                            {{ old('role', 'operator') === $value ? 'border-primary bg-primary-fixed/40' : 'border-outline-variant/40 bg-surface-container-lowest hover:border-outline-variant' }}">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $color }}">
                                {{ $label }}
                            </span>
                            <p class="mt-2 text-xs text-on-surface-variant">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div x-data="{ show: false }">
                <label for="password" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Password <span class="text-error">*</span>
                </label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                           placeholder="Min. 8 karakter"
                           class="block w-full rounded-xl border px-3.5 py-2.5 pl-3.5 pr-10 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('password') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
                @error('password')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Konfirmasi Password --}}
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Konfirmasi Password <span class="text-error">*</span>
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       placeholder="Ulangi password"
                       class="block w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10 focus:ring-offset-1"/>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/20 pt-4">
                <a href="{{ route('users.index') }}"
                   class="rounded-xl bg-surface-container px-5 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container-high">
                    Batal
                </a>
                <button type="submit"
                        class="primary-gradient flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Tambah Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
