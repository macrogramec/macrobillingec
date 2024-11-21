<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiLogger;
use Symfony\Component\HttpFoundation\Response;

class ApiLogging
{
    protected $apiLogger;

    public function __construct(ApiLogger $apiLogger)
    {
        $this->apiLogger = $apiLogger;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Registrar la petición entrante
        $startTime = microtime(true);
        $this->apiLogger->logRequest($request);

        // Procesar la petición
        $response = $next($request);

        // Calcular duración
        $duration = microtime(true) - $startTime;

        // Registrar la respuesta
        $this->apiLogger->logResponse($response, $request, $duration);

        return $response;
    }
}