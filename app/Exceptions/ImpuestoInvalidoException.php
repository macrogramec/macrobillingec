<?php

namespace App\Exceptions;

class ImpuestoInvalidoException extends FacturacionException
{
    public function __construct(string $message = "", array $errors = [])
    {
        parent::__construct(
            $message ?: 'Error en la validación de impuestos',
            $errors,
            422
        );
    }
}
