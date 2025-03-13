<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción específica para errores relacionados con Guías de Remisión
 *
 * Esta excepción debe ser lanzada cuando ocurran errores en la validación,
 * procesamiento o emisión de guías de remisión electrónicas.
 */
class GuiaRemisionException extends Exception
{
    /**
     * Información adicional sobre el error
     *
     * @var array
     */
    protected $errorInfo;

    /**
     * Constructor para la excepción
     *
     * @param string $message Mensaje descriptivo del error
     * @param int $code Código de error opcional
     * @param array $errorInfo Información adicional opcional sobre el error
     * @param \Throwable|null $previous Excepción previa que causó este error
     */
    public function __construct(
        string $message = "Error en la Guía de Remisión",
        int $code = 0,
        array $errorInfo = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorInfo = $errorInfo;
    }

    /**
     * Obtiene la información adicional del error
     *
     * @return array
     */
    public function getErrorInfo(): array
    {
        return $this->errorInfo;
    }

    /**
     * Añade información específica de error
     *
     * @param string $key Clave para la información
     * @param mixed $value Valor asociado
     * @return void
     */
    public function addErrorInfo(string $key, $value): void
    {
        $this->errorInfo[$key] = $value;
    }

    /**
     * Representación en cadena de la excepción
     *
     * @return string
     */
    public function __toString(): string
    {
        $errorDetails = '';
        if (!empty($this->errorInfo)) {
            $errorDetails = ' [Detalles: ' . json_encode($this->errorInfo) . ']';
        }

        return get_class($this) . " '{$this->message}'{$errorDetails} en {$this->file}({$this->line})\n"
            . ($this->previous ? " causado por " . $this->previous . "\n" : '');
    }
}
