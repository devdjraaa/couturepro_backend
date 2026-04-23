<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth('admin')->user();

        if (! $admin || ! $admin->hasPermission($permission)) {
            return response()->json(['message' => 'Permission insuffisante.'], 403);
        }

        return $next($request);
    }
}
