<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => 'Email atau nomor telepon wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Tentukan field: email atau phone
        $isEmail = filter_var($request->login, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        $credentials = [
            $field     => $request->login,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            // Cek akun aktif
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'login' => 'Akun Anda telah dinonaktifkan. Hubungi administrator untuk informasi lebih lanjut.',
                ])->withInput($request->only('login', 'remember'));
            }

            // Catat waktu login terakhir
            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'login' => 'Email/telepon atau password yang Anda masukkan tidak sesuai.',
        ])->withInput($request->only('login', 'remember'));
    }

    // ─── Register (hanya jika belum ada Super Admin) ──────────────────────────

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Tutup registrasi jika super admin sudah ada
        if (User::where('role', 'super_admin')->exists()) {
            return redirect()->route('login')
                ->with('info', 'Pendaftaran ditutup. Hubungi administrator untuk mendapatkan akses.');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Double-check: hanya izinkan jika belum ada super admin
        if (User::where('role', 'super_admin')->exists()) {
            return redirect()->route('login')
                ->with('info', 'Pendaftaran ditutup.');
        }

        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'phone'                 => 'required|string|max:20|unique:users,phone',
            'password'              => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required'              => 'Nama lengkap wajib diisi.',
            'email.required'             => 'Email wajib diisi.',
            'email.email'                => 'Format email tidak valid.',
            'email.unique'               => 'Email sudah terdaftar.',
            'phone.required'             => 'Nomor telepon wajib diisi.',
            'phone.unique'               => 'Nomor telepon sudah terdaftar.',
            'password.required'          => 'Password wajib diisi.',
            'password.confirmed'         => 'Konfirmasi password tidak cocok.',
            'password.letters'           => 'Password harus mengandung minimal satu huruf.',
            'password.numbers'           => 'Password harus mengandung minimal satu angka.',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'password'   => Hash::make($request->password),
            'role'       => 'super_admin', // Pengguna pertama selalu Super Admin
            'is_active'  => true,
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Selamat datang, ' . $user->name . '! Akun Super Admin berhasil dibuat.');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
