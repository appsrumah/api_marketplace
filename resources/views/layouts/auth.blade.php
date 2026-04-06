<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — Kios Q</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

<div class="flex min-h-full">

    {{-- ===== LEFT PANEL — Branding ===== --}}
    <div class="hidden lg:flex lg:w-[46%] flex-col justify-between bg-slate-900 p-10 xl:p-14 relative overflow-hidden">

        {{-- Decorative circles --}}
        <div class="absolute -top-20 -left-20 h-72 w-72 rounded-full bg-cyan-500/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-blue-600/15 blur-3xl"></div>

        {{-- Logo --}}
        <div>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white shadow-lg">
                    KQ
                </div>
                <div>
                    <span class="text-lg font-bold text-white">Kios Q</span>
                    <span class="ml-1 text-sm font-normal text-slate-400">Omnichannel</span>
                </div>
            </div>
        </div>

        {{-- Main content --}}
        <div class="relative">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3 py-1.5 text-xs font-medium text-cyan-400">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                Platform Omnichannel Indonesia
            </div>
            <h1 class="text-3xl font-extrabold text-white xl:text-4xl leading-tight">
                Kelola Semua<br>
                <span class="bg-linear-to-r from-cyan-400 to-blue-400 bg-clip-text text-transparent">Marketplace</span><br>
                Dalam Satu Tempat
            </h1>
            <p class="mt-4 text-sm text-slate-400 leading-relaxed max-w-xs">
                Platform terpadu untuk mengelola stok, pesanan, dan produk di berbagai marketplace secara otomatis.
            </p>

            {{-- Feature list --}}
            <ul class="mt-8 space-y-3">
                @foreach([
                    ['Sinkronisasi stok otomatis ke semua marketplace', 'from-emerald-500 to-teal-500'],
                    ['Kelola TikTok Shop, Tokopedia, Shopee & lebih', 'from-cyan-500 to-blue-500'],
                    ['Update stok real-time dari sistem POS', 'from-violet-500 to-purple-500'],
                    ['Multi-akun & multi-pengguna dengan kontrol akses', 'from-amber-500 to-orange-500'],
                ] as [$text, $gradient])
                <li class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-linear-to-br {{ $gradient }}">
                        <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm text-slate-300">{{ $text }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        {{-- Bottom --}}
        <div class="relative text-xs text-slate-600">
            © {{ date('Y') }} Kios Q. Semua hak dilindungi.
        </div>
    </div>

    {{-- ===== RIGHT PANEL — Form ===== --}}
    <div class="flex flex-1 flex-col justify-center px-6 py-12 lg:px-12 xl:px-16">

        {{-- Mobile logo --}}
        <div class="mb-8 flex items-center gap-3 lg:hidden">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-blue-600 text-sm font-bold text-white">
                KQ
            </div>
            <span class="text-base font-bold text-slate-800">Kios Q Omnichannel</span>
        </div>

        <div class="w-full max-w-md mx-auto">

            {{-- Flash messages --}}
            @if(session('info'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    {{ session('info') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('form')
        </div>
    </div>

</div>

@stack('scripts')
</body>
</html>
