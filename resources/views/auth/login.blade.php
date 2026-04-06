@extends('layouts.auth')
@section('title', 'Masuk ke Akun Anda')

@section('form')
<div x-data="{ showPass: false }">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-[1.75rem] font-black text-slate-900 leading-tight tracking-tight">Selamat Datang! 👋</h2>
        <p class="mt-1.5 text-[13.5px] text-slate-500">Masuk untuk mengelola marketplace Anda.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Login field (email atau telepon) --}}
        <div class="space-y-1.5">
            <label for="login" class="block text-[13px] font-semibold text-slate-700">
                Email / Nomor Telepon
            </label>
            <input
                id="login"
                name="login"
                type="text"
                autocomplete="username"
                value="{{ old('login') }}"
                placeholder="email@contoh.com atau 08xx..."
                class="w-full rounded-xl border px-4 py-3 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('login') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}"
            />
            @error('login')
            <p class="flex items-center gap-1.5 text-[12px] text-red-600">
                <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="space-y-1.5">
            <label for="password" class="block text-[13px] font-semibold text-slate-700">
                Password
            </label>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    :type="showPass ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="Masukkan password Anda"
                    class="w-full rounded-xl border px-4 py-3 pr-12 text-[13.5px] text-slate-900 placeholder-slate-400 outline-none transition duration-150 {{ $errors->has('password') ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-100' : 'border-slate-200 bg-slate-50 hover:border-slate-300 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100' }}"
                />
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
            @error('password')
            <p class="flex items-center gap-1.5 text-[12px] text-red-600">
                <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center">
            <input id="remember" name="remember" type="checkbox"
                   class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
            <label for="remember" class="ml-2 text-[13px] text-slate-600 cursor-pointer select-none">
                Ingat saya selama 30 hari
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="mt-1 w-full rounded-xl bg-linear-to-r from-cyan-500 to-blue-600 px-4 py-3.5 text-[13.5px] font-bold text-white shadow-lg shadow-blue-500/20 hover:from-cyan-600 hover:to-blue-700 hover:shadow-blue-500/30 active:scale-[0.98] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Masuk ke Dashboard →
        </button>
    </form>

    {{-- Divider --}}
    <div class="mt-6 flex items-center gap-3">
        <div class="flex-1 border-t border-slate-200"></div>
        <span class="text-[11px] text-slate-400 font-medium">atau</span>
        <div class="flex-1 border-t border-slate-200"></div>
    </div>

    <p class="mt-4 text-center text-[13px] text-slate-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline transition">
            Setup Super Admin
        </a>
    </p>

</div>
@endsection
