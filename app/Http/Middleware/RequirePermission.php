<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware untuk cek permission user berdasarkan sistem role-permission.
 *
 * Penggunaan di route:
 *   ->middleware('permission:products.view')
 *   ->middleware('permission:products.edit,products.create')  // salah satu harus punya
 */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        // Super Admin otomatis bypass semua permission
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Cek minimal salah satu permission terpenuhi
        foreach ($permissions as $perm) {
            if ($user->hasPermission($perm)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengakses fitur ini.',
            ], 403);
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
