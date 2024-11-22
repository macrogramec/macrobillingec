<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class ApiLogger
{
    /**
     * Registrar una petición API
     */
    public function logRequest(Request $request, string $level = 'info'): void
    {
        // No logueamos las contraseñas
        $input = $request->except(['password', 'password_confirmation']);
        
        $logData = [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'user_scopes' => $request->user()?->scopes,
            'headers' => $this->getRelevantHeaders($request),
            'payload' => $input,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('api')->$level('API Request', $logData);
    }

    /**
     * Registrar una respuesta API
     */
    public function logResponse($response, Request $request, float $duration = null): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'duration' => $duration ? round($duration, 3) . 's' : null,
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toIso8601String(),
        ];

        // Solo logueamos el contenido si no es exitoso
        if ($response->status() >= 400) {
            $logData['content'] = json_decode($response->getContent(), true);
            Log::channel('api_errors')->error('API Error Response', $logData);
        } else {
            Log::channel('api')->info('API Response', $logData);
        }
    }

    /**
     * Registrar un error de API
     */
    public function logError(Exception $exception, Request $request): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('api_errors')->error('API Exception', $logData);
    }

    /**
     * Obtener headers relevantes para el log
     */
    private function getRelevantHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        
        // Filtramos headers sensibles
        $sensitiveHeaders = ['authorization', 'cookie'];
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }

        return $headers;
    }
}