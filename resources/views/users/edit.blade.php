@extends('layouts.app')
@section('title', 'Edit Pengguna — ' . $user->name)
@section('breadcrumb', 'Pengguna — Edit')

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- HEADER --}}
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('users.index') }}"
           class="flex items-center justify-center rounded-xl bg-surface-container p-2 text-on-surface-variant transition hover:bg-surface-container-high">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h1 class="font-headline text-xl font-bold text-primary">Edit Pengguna</h1>
            <p class="text-sm text-on-surface-variant">Ubah data dan hak akses {{ $user->name }}</p>
        </div>
    </div>

    {{-- USER CARD --}}
    <div class="mb-5 flex items-center gap-4 rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-base font-bold text-white shadow
            {{ $user->isSuperAdmin() ? 'primary-gradient' : ($user->isAdmin() ? 'bg-primary-container' : 'bg-surface-container-highest text-on-surface') }}">
            {{ $user->initials }}
        </div>
        <div>
            <p class="font-semibold text-on-surface">{{ $user->name }}</p>
            <div class="mt-1 flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $user->role_color }}">
                    {{ $user->role_label }}
                </span>
                @if($user->last_login_at)
                    <span class="text-xs text-on-surface-variant">Login terakhir {{ $user->last_login_at->diffForHumans() }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- FORM DATA --}}
    <div class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
        <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
            <p class="text-sm font-bold text-on-surface">Data Pengguna</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5 p-6">
            @csrf
            @method('PUT')

            {{-- Nama --}}
            <div>
                <label for="name" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nama Lengkap <span class="text-error">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('name') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Email <span class="text-error">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('email') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Telepon --}}
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    Nomor Telepon <span class="text-sm font-normal text-on-surface-variant">(opsional)</span>
                </label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-on-surface placeholder-on-surface-variant/40 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('phone') ? 'border-error bg-error-container/10 focus:ring-error/20' : 'border-outline-variant/40 bg-surface-container-lowest focus:border-primary focus:ring-primary/10' }}"/>
                @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            {{-- Peran --}}
            @if(!$user->isSuperAdmin())
            <div>
                <label class="mb-2 block text-sm font-semibold text-on-surface">
                    Peran / Role <span class="text-error">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['admin' => ['Admin', 'bg-primary-fixed text-primary', 'Kelola marketplace & produk'], 'operator' => ['Operator', 'bg-surface-container text-on-surface-variant', 'Lihat data & jalankan sync stok']] as $value => [$label, $color, $desc])
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}"
                               {{ old('role', $user->role) === $value ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="rounded-xl border-2 p-4 transition peer-checked:border-primary peer-checked:bg-primary-fixed/40
                            {{ old('role', $user->role) === $value ? 'border-primary bg-primary-fixed/40' : 'border-outline-variant/40 bg-surface-container-lowest hover:border-outline-variant' }}">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $color }}">{{ $label }}</span>
                            <p class="mt-2 text-xs text-on-surface-variant">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            @else
            <input type="hidden" name="role" value="super_admin">
            @endif

            {{-- Reset Password --}}
            <div x-data="{ changePass: false, show: false }" class="rounded-xl bg-surface-container-low p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-on-surface">Ganti Password</p>
                        <p class="text-xs text-on-surface-variant">Kosongkan jika tidak ingin mengubah</p>
                    </div>
                    <button type="button" @click="changePass = !changePass"
                            class="text-xs font-semibold text-primary hover:underline">
                        <span x-text="changePass ? 'Batalkan' : 'Ganti Password'"></span>
                    </button>
                </div>
                <div x-show="changePass" x-cloak class="mt-4 space-y-4">
                    <div class="relative">
                        <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Password Baru</label>
                        <input name="password" :type="show ? 'text' : 'password'"
                               placeholder="Min. 8 karakter"
                               class="block w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2.5 pl-3.5 pr-10 text-sm placeholder-on-surface-variant/40 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10"/>
                        <button type="button" @click="show = !show"
                                class="absolute right-0 flex items-center pr-3 text-on-surface-variant hover:text-on-surface" style="top:26px">
                            <span class="material-symbols-outlined text-[18px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                        </button>
                        @error('password')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-on-surface-variant">Konfirmasi Password Baru</label>
                        <input name="password_confirmation" :type="show ? 'text' : 'password'"
                               placeholder="Ulangi password baru"
                               class="block w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest py-2.5 px-3.5 text-sm placeholder-on-surface-variant/40 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/10"/>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-outline-variant/20 pt-4">
                <a href="{{ route('users.index') }}"
                   class="rounded-xl bg-surface-container px-5 py-2.5 text-sm font-semibold text-on-surface-variant transition hover:bg-surface-container-high">
                    Batal
                </a>
                <button type="submit"
                        class="primary-gradient flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection