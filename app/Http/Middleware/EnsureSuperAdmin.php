<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!Auth::check() || !$user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Hanya Super Admin.'], 403);
            }
            abort(403, 'Akses ditolak. Halaman ini hanya untuk Super Admin.');
        }

        return $next($request);
    }
}
