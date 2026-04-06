@extends('layouts.app')
@section('title', 'Manajemen Pengguna')

@section('content')

{{-- ===== HEADER ===== --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">
            Kelola sub-akun dan hak akses pengguna sistem.
        </p>
    </div>
    <a href="{{ route('users.create') }}"
       class="inline-flex items-center gap-2 rounded-xl bg-linear-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:from-cyan-600 hover:to-blue-700 active:scale-[0.98]">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        Tambah Pengguna
    </a>
</div>

{{-- ===== STATS ===== --}}
<div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-2xl font-bold text-slate-900">{{ $totalUsers }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Total Pengguna</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-2xl font-bold text-emerald-600">{{ $totalActive }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Aktif</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-2xl font-bold text-blue-600">{{ $users->where('role', 'admin')->count() }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Admin</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-2xl font-bold text-slate-600">{{ $users->where('role', 'operator')->count() }}</p>
        <p class="mt-1 text-xs font-medium text-slate-500">Operator</p>
    </div>
</div>

{{-- ===== USER TABLE ===== --}}
<div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
        <h2 class="text-sm font-bold text-slate-900">Daftar Pengguna</h2>
        <p class="text-xs text-slate-500">Sub-akun yang dapat mengakses sistem ini</p>
    </div>

    @if($users->isEmpty())
        <div class="flex flex-col items-center py-16 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
                <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="mt-3 text-sm font-medium text-slate-700">Belum ada sub-akun</p>
            <p class="mt-1 text-xs text-slate-500">Tambahkan pengguna untuk berbagi akses sistem.</p>
            <a href="{{ route('users.create') }}"
               class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Pertama
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Pengguna</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kontak</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Peran</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Login Terakhir</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($users as $user)
                    <tr class="transition hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl
                                    {{ $user->isSuperAdmin() ? 'bg-linear-to-br from-violet-500 to-purple-600' : ($user->isAdmin() ? 'bg-linear-to-br from-cyan-500 to-blue-600' : 'bg-linear-to-br from-slate-400 to-slate-500') }}
                                    text-sm font-bold text-white shadow-sm">
                                    {{ $user->initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        Dibuat {{ $user->created_at->diffForHumans() }}
                                        @if($user->creator)
                                            oleh {{ $user->creator->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-700">{{ $user->email }}</p>
                            @if($user->phone)
                                <p class="text-xs text-slate-500">{{ $user->phone }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role_color }}">
                                {{ $user->role_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            @if($user->last_login_at)
                                <span title="{{ $user->last_login_at->format('d M Y H:i') }}">
                                    {{ $user->last_login_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-slate-400 italic">Belum pernah</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Edit --}}
                                <a href="{{ route('users.edit', $user) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-blue-100 hover:text-blue-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </a>

                                @if(!$user->isSuperAdmin())
                                {{-- Toggle Aktif --}}
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition
                                                {{ $user->is_active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                            onclick="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ addslashes($user->name) }}?')">
                                        @if($user->is_active)
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Nonaktifkan
                                        @else
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Aktifkan
                                        @endif
                                    </button>
                                </form>

                                {{-- Hapus --}}
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100"
                                            onclick="return confirm('Hapus akun {{ addslashes($user->name) }}? Tindakan ini tidak dapat dibatalkan.')">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
        <div class="border-t border-slate-100 px-6 py-4">
            {{ $users->links() }}
        </div>
        @endif
    @endif
</div>

{{-- ===== ROLE GUIDE ===== --}}
<div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 mb-4">Panduan Hak Akses</h3>
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach([
            ['Super Admin', 'super_admin', 'bg-violet-100 text-violet-700', 'Akses penuh ke seluruh sistem termasuk manajemen pengguna, akun marketplace, produk, dan sinkronisasi stok.'],
            ['Admin', 'admin', 'bg-blue-100 text-blue-700', 'Dapat mengelola akun marketplace, produk, dan sinkronisasi stok. Tidak dapat mengelola pengguna.'],
            ['Operator', 'operator', 'bg-slate-100 text-slate-600', 'Hanya dapat melihat data dan menjalankan sinkronisasi stok. Tidak dapat mengubah konfigurasi.'],
        ] as [$label, $role, $color, $desc])
        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $color }}">{{ $label }}</span>
            <p class="mt-2 text-xs text-slate-600 leading-relaxed">{{ $desc }}</p>
        </div>
        @endforeach
    </div>
</div>

@endsection
