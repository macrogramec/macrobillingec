<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RucValidoRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Verificar longitud
        if (strlen($value) !== 13) {
            $fail('El RUC debe tener 13 dígitos.');
            return;
        }

        // Verificar que sean solo números
        if (!preg_match('/^[0-9]+$/', $value)) {
            $fail('El RUC debe contener solo números.');
            return;
        }

        // Obtener dígito verificador
        $digitoVerificador = substr($value, -3, 1);
        $tipoRuc = substr($value, 2, 1);

        // Validar según tipo de RUC
        switch ($tipoRuc) {
            case '6': // Empresas públicas
                if (!$this->validarEmpresaPublica($value)) {
                    $fail('RUC de empresa pública inválido.');
                }
                break;
            case '9': // Personas jurídicas
                if (!$this->validarPersonaJuridica($value)) {
                    $fail('RUC de persona jurídica inválido.');
                }
                break;
            default: // Personas naturales
                if (!$this->validarPersonaNatural($value)) {
                    $fail('RUC de persona natural inválido.');
                }
        }
    }

    protected function validarEmpresaPublica(string $ruc): bool
    {
        $coeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += intval(substr($ruc, $i, 1)) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoVerificador = $residuo === 0 ? 0 : (11 - $residuo);

        return intval(substr($ruc, 8, 1)) === $digitoVerificador;
    }

    protected function validarPersonaJuridica(string $ruc): bool
    {
        $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $suma += intval(substr($ruc, $i, 1)) * $coeficientes[$i];
        }

        $residuo = $suma % 11;
        $digitoVerificador = $residuo === 0 ? 0 : (11 - $residuo);

        return intval(substr($ruc, 9, 1)) === $digitoVerificador;
    }

    protected function validarPersonaNatural(string $ruc): bool
    {
        // Verificar los dos primeros dígitos (provincia)
        $provincia = intval(substr($ruc, 0, 2));
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        // Validar cédula (primeros 10 dígitos)
        $cedula = substr($ruc, 0, 10);
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval(substr($cedula, $i, 1)) * $coeficientes[$i];
            $suma += ($valor > 9) ? $valor - 9 : $valor;
        }

        $digitoVerificador = $suma % 10 === 0 ? 0 : (10 - ($suma % 10));

        return intval(substr($cedula, -1)) === $digitoVerificador;
    }
}
