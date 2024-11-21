<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ClientRateLimiting
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        // Configuramos las rutas administrativas para tener límites más altos
        $isAdminRoute = str_starts_with($request->path(), 'api/create-user');
        
        // Clave única para el rate limiting que incluye la ruta
        $key = sprintf(
            'api:%s:%s',
            $user->id,
            $isAdminRoute ? 'admin' : 'general'
        );

        // Establecemos límites diferentes según la ruta y los scopes
        $limit = $this->getLimit($user->scopes ?? [], $isAdminRoute);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Límite de peticiones excedido',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        RateLimiter::hit($key);

        $response = $next($request);

        // Agregamos headers informativos
        $response->header('X-RateLimit-Limit', $limit);
        $response->header('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit));
        $response->header('X-RateLimit-Reset', RateLimiter::availableIn($key));

        return $response;
    }

    private function getLimit(array $scopes, bool $isAdminRoute): int
    {
        // Rutas administrativas tienen límites más altos
        if ($isAdminRoute && in_array('admin', $scopes)) {
            return 1000; // 1000 peticiones por minuto para rutas admin
        }

        // Para otras rutas, verificamos los scopes
        if (in_array('admin', $scopes)) {
            return 500; // 500 peticiones por minuto para admins en rutas generales
        }

        if (in_array('desarrollo', $scopes)) {
            return 200; // 200 peticiones por minuto para desarrollo
        }

        if (in_array('produccion', $scopes)) {
            return 300; // 300 peticiones por minuto para producción
        }

        return 100; // límite base para usuarios normales
    }
}