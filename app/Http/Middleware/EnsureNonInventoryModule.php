<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNonInventoryModule
{
    /**
     * Handle an incoming request.
     * Rejects access for Admin Inventory users attempting to access prohibited modules.
     * Other roles (Admin Management, User, etc.) pass through unaffected.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->isActive()) {
            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif. Silakan hubungi Administrator.',
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Akun Anda tidak aktif. Silakan hubungi Administrator.');
        }

        if (Auth::check() && Auth::user()->isAdminInventory()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Akun Admin Inventory hanya memiliki izin untuk mengakses fitur Peminjaman Barang.',
                ], 403);
            }

            abort(403, 'Akses ditolak. Akun Admin Inventory hanya memiliki izin untuk mengakses fitur Peminjaman Barang.');
        }

        return $next($request);
    }
}

