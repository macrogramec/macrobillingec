<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Construcción de respuesta exitosa
     */
    protected function successResponse($data, string $message = null, int $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Construcción de respuesta de error
     */
    protected function errorResponse(string $message, int $code, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'code' => $this->getErrorCode($code)
        ], $code);
    }

    /**
     * Respuesta para errores de validación
     */
    protected function validationErrorResponse($errors, string $message = 'Error de validación')
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Respuesta cuando no se encuentra un recurso
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta para errores de autorización
     */
    protected function unauthorizedResponse(string $message = 'No autorizado')
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta para errores de permisos
     */
    protected function forbiddenResponse(string $message = 'Acceso denegado')
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Obtener código de error estandarizado
     */
    private function getErrorCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'SERVER_ERROR',
            default => 'UNKNOWN_ERROR',
        };
    }
}
