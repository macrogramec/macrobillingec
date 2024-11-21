<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckScopes
{
    public function handle(Request $request, Closure $next, ...$scopes)
    {
        $user = $request->user();

        if (!$user->hasScopes($scopes)) {
            return response()->json([
                'error' => 'No tienes permisos para acceder'
            ], 403);
        }

        return $next($request);
    }
}