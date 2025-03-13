<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;

class ProveedorValidoRule implements Rule
{
    protected $version;
    protected $message = '';

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function passes($attribute, $value): bool
    {
        $tipoIdentificacion = request('proveedor.tipo_identificacion');

        // Validaciones comunes para todas las versiones
        if (!$this->validarLongitudIdentificacion($tipoIdentificacion, $value)) {
            return false;
        }

        // Validaciones específicas por versión
        switch ($this->version) {
            case '1.0.0':
                return $this->validarVersion100($tipoIdentificacion, $value);
            case '1.1.0':
                return $this->validarVersion110($tipoIdentificacion, $value);
            case '2.0.0':
            case '2.1.0':
                return $this->validarVersion200Plus($tipoIdentificacion, $value);
            default:
                $this->message = 'Versión no soportada.';
                return false;
        }
    }

    protected function validarLongitudIdentificacion($tipo, $identificacion): bool
    {
        $longitudes = [
            '04' => 10, // RUC
            '05' => 13, // Cédula
            '06' => 13, // Pasaporte
            '07' => 13, // Consumidor Final
            '08' => 13  // Identificación del Exterior
        ];

        if (!isset($longitudes[$tipo])) {
            $this->message = 'Tipo de identificación no válido.';
            return false;
        }

        if (strlen($identificacion) != $longitudes[$tipo]) {
            $this->message = "La identificación debe tener {$longitudes[$tipo]} caracteres para el tipo $tipo.";
            return false;
        }

        return true;
    }

    protected function validarVersion100($tipo, $identificacion): bool
    {
        // En 1.0.0 solo se permiten RUC y Cédula
        if (!in_array($tipo, ['04', '05'])) {
            $this->message = 'Para la versión 1.0.0 solo se permiten RUC y Cédula.';
            return false;
        }

        return $this->validarAlgoritmoIdentificacion($tipo, $identificacion);
    }

    protected function validarVersion110($tipo, $identificacion): bool
    {
        // En 1.1.0 se añade soporte para pasaporte
        if (!in_array($tipo, ['04', '05', '06'])) {
            $this->message = 'Para la versión 1.1.0 solo se permiten RUC, Cédula y Pasaporte.';
            return false;
        }

        if ($tipo === '06') return true; // Pasaporte no tiene validación de dígito
        return $this->validarAlgoritmoIdentificacion($tipo, $identificacion);
    }

    protected function validarVersion200Plus($tipo, $identificacion): bool
    {
        // En 2.0.0+ se añade soporte para consumidor final e identificación del exterior
        if (!in_array($tipo, ['04', '05', '06', '07', '08'])) {
            $this->message = 'Tipo de identificación no válido para la versión especificada.';
            return false;
        }

        if (in_array($tipo, ['06', '07', '08'])) return true; // Sin validación de dígito
        return $this->validarAlgoritmoIdentificacion($tipo, $identificacion);
    }

    protected function validarAlgoritmoIdentificacion($tipo, $identificacion): bool
    {
        // Implementar validación de algoritmo según tipo
        if ($tipo === '04' || $tipo === '05') {
            if (!$this->validarCedulaEcuatoriana($identificacion)) {
                $this->message = 'La identificación no cumple con el algoritmo de validación.';
                return false;
            }
        }
        return true;
    }

    protected function validarCedulaEcuatoriana($cedula): bool
    {
        if (preg_match('/^[0-9]{10,13}$/', $cedula) !== 1) {
            return false;
        }

        $cedula = substr($cedula, 0, 10);
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]) * $coeficientes[$i];
            $suma += ($valor >= 10) ? $valor - 9 : $valor;
        }

        $digitoVerificador = (ceil($suma / 10) * 10) - $suma;
        if ($digitoVerificador == 10) {
            $digitoVerificador = 0;
        }

        return $digitoVerificador == intval($cedula[9]);
    }

    public function message(): string
    {
        return $this->message;
    }
}
