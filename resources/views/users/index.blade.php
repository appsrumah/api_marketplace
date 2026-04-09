@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('breadcrumb', 'Pengguna â€” Manajemen Akun')

@section('content')

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HEADER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-secondary">Manajemen</p>
        <h1 class="font-headline text-3xl font-extrabold tracking-tight text-primary">Pengguna</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Kelola sub-akun dan hak akses pengguna sistem.</p>
    </div>
    <a href="{{ route('users.create') }}"
       class="primary-gradient inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white shadow-primary-glow transition hover:opacity-90 active:scale-[0.98]">
        <span class="material-symbols-outlined text-[18px]">person_add</span>
        Tambah Pengguna
    </a>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     STATS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="mt-6 grid grid-cols-2 gap-5 sm:grid-cols-4">
    <div class="rounded-2xl bg-surface-container-lowest p-5 shadow-whisper">
        <p class="font-headline text-2xl font-bold text-on-surface">{{ $totalUsers }}</p>
        <p class="mt-1 text-xs font-medium text-on-surface-variant">Total Pengguna</p>
    </div>
    <div class="rounded-2xl bg-secondary-container/40 p-5 shadow-whisper">
        <p class="font-headline text-2xl font-bold text-on-secondary-container">{{ $totalActive }}</p>
        <p class="mt-1 text-xs font-medium text-on-secondary-container/80">Aktif</p>
    </div>
    <div class="rounded-2xl bg-primary-fixed p-5 shadow-whisper">
        <p class="font-headline text-2xl font-bold text-primary">{{ $users->where('role', 'admin')->count() }}</p>
        <p class="mt-1 text-xs font-medium text-on-surface-variant">Admin</p>
    </div>
    <div class="rounded-2xl bg-surface-container-low p-5 shadow-whisper">
        <p class="font-headline text-2xl font-bold text-on-surface">{{ $users->where('role', 'operator')->count() }}</p>
        <p class="mt-1 text-xs font-medium text-on-surface-variant">Operator</p>
    </div>
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     USER TABLE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="mt-8 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
    <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
        <h2 class="text-sm font-bold text-on-surface">Daftar Pengguna</h2>
        <p class="text-xs text-on-surface-variant">Sub-akun yang dapat mengakses sistem ini</p>
    </div>

    @if($users->isEmpty())
        <div class="flex flex-col items-center py-16 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-fixed">
                <span class="material-symbols-outlined text-[32px] text-primary">group</span>
            </div>
            <p class="mt-3 text-sm font-semibold text-on-surface">Belum ada sub-akun</p>
            <p class="mt-1 text-xs text-on-surface-variant">Tambahkan pengguna untuk berbagi akses sistem.</p>
            <a href="{{ route('users.create') }}"
               class="primary-gradient mt-4 inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-primary-glow transition hover:opacity-90">
                <span class="material-symbols-outlined text-[16px]">add</span>
                Tambah Pertama
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-outline-variant/10">
                <thead>
                    <tr class="bg-surface-container-low">
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pengguna</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kontak</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-on-surface-variant">Peran</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-on-surface-variant">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-on-surface-variant">Login Terakhir</th>
                        <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-on-surface-variant">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @foreach($users as $user)
                    <tr class="transition hover:bg-surface-container-low">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold text-white shadow-sm
                                    {{ $user->isSuperAdmin() ? 'primary-gradient' : ($user->isAdmin() ? 'bg-primary-container' : 'bg-surface-container-highest text-on-surface') }}">
                                    {{ $user->initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">{{ $user->name }}</p>
                                    <p class="text-xs text-on-surface-variant">
                                        Dibuat {{ $user->created_at->diffForHumans() }}
                                        @if($user->creator)
                                            oleh {{ $user->creator->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-on-surface">{{ $user->email }}</p>
                            @if($user->phone)
                                <p class="text-xs text-on-surface-variant">{{ $user->phone }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role_color }}">
                                {{ $user->role_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container px-2.5 py-1 text-xs font-semibold text-on-secondary-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-error-container/40 px-2.5 py-1 text-xs font-semibold text-on-error-container">
                                    <span class="h-1.5 w-1.5 rounded-full bg-error"></span> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-on-surface-variant">
                            @if($user->last_login_at)
                                <span title="{{ $user->last_login_at->format('d M Y H:i') }}">
                                    {{ $user->last_login_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="italic text-on-surface-variant/50">Belum pernah</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Edit --}}
                                <a href="{{ route('users.edit', $user) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-tertiary-fixed px-3 py-1.5 text-xs font-semibold text-on-tertiary-fixed-variant transition hover:opacity-80">
                                    <span class="material-symbols-outlined text-[14px]">edit</span>
                                    Edit
                                </a>

                                @if(!$user->isSuperAdmin())
                                {{-- Toggle Aktif --}}
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition
                                                {{ $user->is_active ? 'bg-tertiary-fixed/60 text-on-tertiary-fixed-variant hover:opacity-80' : 'bg-secondary-container text-on-secondary-container hover:opacity-80' }}"
                                            onclick="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ addslashes($user->name) }}?')">
                                        <span class="material-symbols-outlined text-[14px]">{{ $user->is_active ? 'block' : 'check_circle' }}</span>
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                {{-- Hapus --}}
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center rounded-lg bg-error-container/30 px-3 py-1.5 text-xs font-semibold text-on-error-container transition hover:bg-error-container/60"
                                            onclick="return confirm('Hapus akun {{ addslashes($user->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                        <span class="material-symbols-outlined text-[14px]">delete</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="border-t border-outline-variant/20 px-6 py-4">
            {{ $users->links() }}
        </div>
        @endif
    @endif
</div>

{{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     ROLE GUIDE
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
<div class="mt-8 overflow-hidden rounded-2xl bg-surface-container-lowest shadow-whisper">
    <div class="border-b border-outline-variant/20 bg-surface-container-low px-6 py-4">
        <h3 class="text-sm font-bold text-on-surface">Panduan Hak Akses</h3>
    </div>
    <div class="grid gap-4 p-6 sm:grid-cols-3">
        @foreach([
            ['Super Admin', 'super_admin', 'bg-primary-fixed text-primary', 'Akses penuh ke seluruh sistem termasuk manajemen pengguna, akun marketplace, produk, dan sinkronisasi stok.'],
            ['Admin', 'admin', 'bg-primary-fixed/60 text-primary', 'Dapat mengelola akun marketplace, produk, dan sinkronisasi stok. Tidak dapat mengelola pengguna.'],
            ['Operator', 'operator', 'bg-surface-container text-on-surface-variant', 'Hanya dapat melihat data dan menjalankan sinkronisasi stok. Tidak dapat mengubah konfigurasi.'],
        ] as [$label, $role, $color, $desc])
        <div class="rounded-xl bg-surface-container-low p-4">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $color }}">{{ $label }}</span>
            <p class="mt-2 text-xs leading-relaxed text-on-surface-variant">{{ $desc }}</p>
        </div>
        @endforeach
    </div>
</div>

@endsection
