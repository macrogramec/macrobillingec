<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckScopes
{
    public function handle(Request $request, Closure $next, ...$scopes)
    {
        $user = $request->user();

        // Log para debugging
        \Log::info('Scopes requeridos antes de split:', $scopes);

        // Separar los scopes que vienen con |
        $requiredScopes = [];
        foreach ($scopes as $scope) {
            $requiredScopes = array_merge(
                $requiredScopes,
                explode('|', $scope)
            );
        }

        \Log::info('Scopes requeridos después de split:', $requiredScopes);
        \Log::info('Scopes del usuario:', ['scopes' => $user->scopes]);

        // Verificar si el usuario tiene AL MENOS UNO de los scopes requeridos
        $userScopes = $user->scopes ?? [];
        $hasValidScope = false;

        foreach ($userScopes as $userScope) {
            if (in_array($userScope, $requiredScopes)) {
                $hasValidScope = true;
                break;
            }
        }

        if (!$hasValidScope) {
            return response()->json([
                'error' => 'No tienes permisos para acceder',
                'scopes_requeridos' => $requiredScopes,
                'scopes_usuario' => $userScopes
            ], 403);
        }

        return $next($request);
    }
}
