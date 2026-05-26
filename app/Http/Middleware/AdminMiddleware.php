<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->user()?->isAdmin()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Anda tidak memiliki akses.',
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
