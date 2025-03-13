<?php

namespace App\Exceptions;

use Exception;

class FacturacionException extends Exception
{
    protected $errors;

    public function __construct(string $message = "", array $errors = [], int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->getErrors(),
            'code' => 'FACTURACION_ERROR'
        ], 422);
    }
}
