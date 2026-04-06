@extends('layouts.auth')
@section('title', 'Setup Akun Super Admin')

@section('form')
<div x-data="{ showPass: false, showPassConf: false }">

    {{-- Header --}}
    <div class="mb-7">
        <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-700 tracking-wide">
            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Setup Awal Sistem
        </div>
        <h2 class="text-[1.75rem] font-black text-slate-900 leading-tight tracking-tight">Buat Akun Super Admin</h2>
        <p class="mt-1.5 text-[13.5px] text-slate-500">
            Akun ini menjadi <strong class="font-semibold text-slate-700">Super Admin</strong> dengan akses penuh ke seluruh sistem.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Nama Lengkap --}}
        <div class="space-y-1.5">
            <label for="name" class="block text-[13px] font-semibold text-slate-700">
                Nama Lengkap <span class="text-red-500">*</span>
            </label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama Anda"
                   class="w-full rounded-xl border px-4 py-3 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('name') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}" />
            @error('name')<p class="text-[12px] text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div class="space-y-1.5">
            <label for="email" class="block text-[13px] font-semibold text-slate-700">
                Email <span class="text-red-500">*</span>
            </label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="admin@toko.com"
                   class="w-full rounded-xl border px-4 py-3 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('email') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}" />
            @error('email')<p class="text-[12px] text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Nomor Telepon --}}
        <div class="space-y-1.5">
            <label for="phone" class="block text-[13px] font-semibold text-slate-700">
                Nomor Telepon <span class="text-red-500">*</span>
            </label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx"
                   class="w-full rounded-xl border px-4 py-3 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('phone') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}" />
            @error('phone')<p class="text-[12px] text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Password --}}
        <div class="space-y-1.5">
            <label for="password" class="block text-[13px] font-semibold text-slate-700">
                Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input id="password" name="password" :type="showPass ? 'text' : 'password'"
                       placeholder="Min. 8 karakter"
                       class="w-full rounded-xl border px-4 py-3 pr-12 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('password') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}" />
                <button type="button" @click="showPass = !showPass"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                    <svg x-show="!showPass" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPass" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')<p class="text-[12px] text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-[13px] font-semibold text-slate-700">
                Konfirmasi Password <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation"
                       :type="showPassConf ? 'text' : 'password'"
                       placeholder="Ulangi password Anda"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100" />
                <button type="button" @click="showPassConf = !showPassConf"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                    <svg x-show="!showPassConf" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg x-show="showPassConf" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Submit --}}
        <div class="pt-2">
            <button type="submit"
                    class="w-full rounded-xl bg-linear-to-r from-emerald-500 to-teal-600 px-4 py-3.5 text-[13.5px] font-bold text-white shadow-lg shadow-emerald-500/20 hover:from-emerald-600 hover:to-teal-700 hover:shadow-emerald-500/30 active:scale-[0.98] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                Buat Akun & Mulai →
            </button>
            <p class="mt-4 text-center text-[13px] text-slate-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline transition">Masuk di sini</a>
            </p>
        </div>
    </form>

</div>
@endsection
