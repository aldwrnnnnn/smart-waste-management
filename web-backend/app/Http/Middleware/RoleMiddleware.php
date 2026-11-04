<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
                ], 403);
            }

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'Halaman tersebut khusus untuk pengguna pelapor.');
            }

            return redirect()->route('user.dashboard')
                ->with('error', 'Akses ditolak. Halaman tersebut khusus Administrator.');
        }

        return $next($request);
    }
}
