<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — Kios Q</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        html, body, * { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full antialiased bg-white">

<div class="flex min-h-full">

    {{-- ===== LEFT PANEL — Branding ===== --}}
    <div class="hidden lg:flex lg:w-[46%] xl:w-[44%] flex-col relative overflow-hidden"
         style="background: radial-gradient(ellipse 80% 60% at 20% 30%, rgba(14,165,233,0.22) 0%, transparent 60%), radial-gradient(ellipse 60% 80% at 80% 70%, rgba(99,102,241,0.2) 0%, transparent 60%), linear-gradient(140deg, #080d24 0%, #0d1640 50%, #070e1a 100%);">

        {{-- Grid pattern --}}
        <div class="absolute inset-0 opacity-[0.032]"
             style="background-image: linear-gradient(rgba(255,255,255,1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,1) 1px, transparent 1px); background-size: 50px 50px;"></div>

        {{-- Glow orbs --}}
        <div class="absolute -top-32 -left-20 w-md h-md rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full bg-sky-400/5 blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col h-full p-10 xl:p-14 z-10">

            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-linear-to-br from-cyan-400 to-blue-600 text-[13px] font-black text-white shadow-lg shadow-cyan-400/30">
                    KQ
                </div>
                <div>
                    <div class="text-xl font-black text-white tracking-tight leading-none">Kios Q</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Omnichannel Platform</div>
                </div>
            </div>

            {{-- Hero Content --}}
            <div class="flex-1 flex flex-col justify-center py-8">

                {{-- Badge --}}
                <div class="mb-6">
                    <span class="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-3.5 py-1.5 text-[11px] font-semibold text-cyan-300 tracking-wide">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        Platform Omnichannel Indonesia
                    </span>
                </div>

                {{-- Headline --}}
                <h1 class="text-[2.1rem] xl:text-[2.5rem] font-black text-white leading-tight tracking-tight">
                    Kelola Semua<br>
                    <span class="bg-linear-to-r from-cyan-300 via-sky-300 to-indigo-300 bg-clip-text text-transparent">Marketplace</span><br>
                    <span class="text-slate-400 font-semibold text-2xl xl:text-[1.85rem]">Dalam Satu Tempat</span>
                </h1>

                <p class="mt-4 text-[13.5px] text-slate-400 leading-relaxed max-w-75">
                    Sinkronisasi stok otomatis & kelola produk dari TikTok Shop, Tokopedia, Shopee secara real-time.
                </p>

                {{-- Stats --}}
                <div class="mt-8 grid grid-cols-3 gap-3">
                    @foreach([['3+','Marketplace'],['Real‑time','Sinkron Stok'],['Multi','Pengguna']] as [$n,$l])
                    <div class="rounded-xl border border-white/7 bg-white/4 p-3.5 text-center backdrop-blur-sm">
                        <div class="text-[15px] font-black text-white">{{ $n }}</div>
                        <div class="text-[10px] text-slate-500 mt-0.5 leading-tight">{{ $l }}</div>
                    </div>
                    @endforeach
                </div>

                {{-- Features --}}
                <div class="mt-8 space-y-3.5">
                    @foreach([
                        ['Sinkronisasi stok otomatis ke semua marketplace', 'from-cyan-500 to-teal-500'],
                        ['Multi-akun & kontrol akses berbasis peran', 'from-violet-500 to-purple-500'],
                        ['Update stok real-time dari sistem POS', 'from-emerald-500 to-green-500'],
                        ['Dashboard analitik & laporan terpadu', 'from-amber-500 to-orange-500'],
                    ] as [$text, $grad])
                    <div class="flex items-center gap-3">
                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-linear-to-br {{ $grad }} shadow-sm shadow-black/20">
                            <svg class="h-2.5 w-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-[13px] text-slate-300">{{ $text }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <p class="text-[11px] text-slate-700">© {{ date('Y') }} Kios Q. Semua hak dilindungi.</p>
        </div>
    </div>

    {{-- ===== RIGHT PANEL — Form ===== --}}
    <div class="flex flex-1 flex-col justify-center bg-white px-6 py-12 sm:px-10 lg:px-14 xl:px-20 overflow-y-auto">

        {{-- Mobile Logo --}}
        <div class="mb-8 flex items-center gap-2.5 lg:hidden">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-linear-to-br from-cyan-500 to-blue-600 text-[13px] font-black text-white">KQ</div>
            <span class="font-black text-slate-900">Kios Q <span class="font-normal text-slate-400 text-sm">Omnichannel</span></span>
        </div>

        <div class="w-full max-w-sm mx-auto">

            {{-- Session flash messages for auth pages (inline) --}}
            @if(session('info'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-[13px] text-blue-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ session('info') }}</span>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-[13px] text-red-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif
            @if(session('success'))
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-[13px] text-emerald-800">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @yield('form')
        </div>
    </div>

</div>

@stack('scripts')
</body>
</html>
