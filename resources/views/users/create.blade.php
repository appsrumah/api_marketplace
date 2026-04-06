@extends('layouts.app')
@section('title', 'Tambah Pengguna')

@section('content')

<div class="mx-auto max-w-2xl">

    {{-- ===== HEADER ===== --}}
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('users.index') }}"
           class="flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Tambah Pengguna Baru</h1>
            <p class="text-sm text-slate-500">Buat sub-akun dengan hak akses terbatas</p>
        </div>
    </div>

    {{-- ===== FORM ===== --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
            <p class="text-sm font-semibold text-slate-700">Data Pengguna</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Nama --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name') }}"
                       placeholder="Nama pengguna"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('name') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Email <span class="text-red-500">*</span>
                </label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       placeholder="email@contoh.com"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('email') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Telepon --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Nomor Telepon
                    <span class="text-slate-400 font-normal">(opsional)</span>
                </label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}"
                       placeholder="08xxxxxxxxxx"
                       class="block w-full rounded-xl border py-2.5 px-3.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                           {{ $errors->has('phone') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                <p class="mt-1 text-xs text-slate-500">Nomor telepon dapat digunakan untuk login.</p>
                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Peran --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Peran / Role <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['admin' => ['Admin', 'bg-blue-100 text-blue-700', 'Kelola marketplace & produk, tidak bisa buat pengguna'], 'operator' => ['Operator', 'bg-slate-100 text-slate-600', 'Lihat data & jalankan sinkronisasi stok saja']] as $value => [$label, $color, $desc])
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}"
                               {{ old('role', 'operator') === $value ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="rounded-xl border-2 p-4 transition peer-checked:border-blue-500 peer-checked:bg-blue-50
                            {{ old('role', 'operator') === $value ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $color }}">
                                {{ $label }}
                            </span>
                            <p class="mt-2 text-xs text-slate-600">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                           placeholder="Min. 8 karakter"
                           class="block w-full rounded-xl border py-2.5 pl-3.5 pr-10 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1
                               {{ $errors->has('password') ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 focus:border-blue-400 focus:ring-blue-400' }}"/>
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600">
                        <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Konfirmasi Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">
                    Konfirmasi Password <span class="text-red-500">*</span>
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       placeholder="Ulangi password"
                       class="block w-full rounded-xl border border-slate-300 py-2.5 px-3.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"/>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                <a href="{{ route('users.index') }}"
                   class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                        class="flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-cyan-600 hover:to-blue-700 active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Tambah Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush

@endsection
