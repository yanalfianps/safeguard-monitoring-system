<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanExportPdf
{
    /**
     * Handle an incoming request.
     * Enforces module-specific PDF export access rules.
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $user = Auth::user();
        $resolvedModule = $module ?: ($request->routeIs('events.*') ? 'events' : null);

        if (!$user || !$user->isActive() || !$user->canExportPdf($resolvedModule)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Fitur unduh / ekspor PDF tidak tersedia untuk role Anda.',
                ], 403);
            }

            abort(403, 'Akses ditolak. Fitur unduh / ekspor PDF tidak tersedia untuk role Anda.');
        }

        return $next($request);
    }
}

