<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // ─── List Pengguna ────────────────────────────────────────────────────────

    public function index()
    {
        $users = User::where('id', '!=', Auth::id())
            ->with('creator')
            ->orderByRaw("FIELD(role, 'super_admin', 'admin', 'operator')")
            ->orderBy('name')
            ->paginate(15);

        $totalUsers  = User::count();
        $totalActive = User::where('is_active', true)->count();

        return view('users.index', compact('users', 'totalUsers', 'totalActive'));
    }

    // ─── Buat Pengguna ────────────────────────────────────────────────────────

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20|unique:users,phone',
            'role'     => 'required|in:admin,operator',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.unique'       => 'Email sudah digunakan.',
            'phone.unique'       => 'Nomor telepon sudah digunakan.',
            'role.required'      => 'Pilih role pengguna.',
            'role.in'            => 'Role tidak valid.',
            'password.required'  => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'is_active'  => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    // ─── Edit Pengguna ────────────────────────────────────────────────────────

    public function edit(User $user)
    {
        // Tidak bisa edit super_admin lain
        if ($user->isSuperAdmin() && $user->id !== Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak dapat mengubah akun Super Admin lain.');
        }

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSuperAdmin() && $user->id !== Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak dapat mengubah akun Super Admin lain.');
        }

        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'role'  => 'required|in:admin,operator',
        ], [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'   => 'Email sudah digunakan.',
            'phone.unique'   => 'Nomor telepon sudah digunakan.',
            'role.required'  => 'Pilih role pengguna.',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role'  => $request->role,
        ]);

        // Update password jika diisi
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Password::min(8)],
            ], [
                'password.confirmed' => 'Konfirmasi password tidak cocok.',
            ]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('users.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    // ─── Toggle Aktif / Nonaktif ─────────────────────────────────────────────

    public function toggleActive(User $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak dapat menonaktifkan akun Super Admin.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    // ─── Hapus Pengguna ───────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak dapat menghapus akun Super Admin.');
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Akun {$name} berhasil dihapus.");
    }
}
