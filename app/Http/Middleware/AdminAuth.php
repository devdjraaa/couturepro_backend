<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if (! $admin) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (! $admin->is_active) {
            return response()->json(['message' => 'Compte admin désactivé.'], 403);
        }

        return $next($request);
    }
}
