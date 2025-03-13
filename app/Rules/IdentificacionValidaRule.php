<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class IdentificacionValidaRule implements Rule
{
    protected $tipoIdentificacion;

    public function __construct($tipoIdentificacion)
    {
        $this->tipoIdentificacion = $tipoIdentificacion;
    }

    public function passes($attribute, $value)
    {
        switch ($this->tipoIdentificacion) {
            case '04': // RUC
                return $this->validarRuc($value);
            case '05': // Cédula
                return $this->validarCedula($value);
            case '06': // Pasaporte
                return $this->validarPasaporte($value);
            case '07': // Consumidor Final
                return $this->validarConsumidorFinal($value);
            case '08': // Identificación del Exterior
                return true; // No se realiza validación para identificaciones del exterior
            default:
                return false;
        }
    }

    /*
    private function validarRuc($ruc)
    {
        // Validar que el RUC tenga 13 dígitos
        if (!preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        // Validar que el tercer dígito sea 6 para sociedades privadas o 9 para personas naturales
        $tercerDigito = substr($ruc, 2, 1);
        if ($tercerDigito != '6' && $tercerDigito != '9') {
            return false;
        }

        // Validar el dígito verificador
        $coeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < count($coeficientes); $i++) {
            $suma += intval(substr($ruc, $i, 1)) * $coeficientes[$i];
        }
        $residuo = $suma % 11;
        $digitoVerificador = $residuo == 0 ? 0 : ($residuo == 1 ? 1 : 11 - $residuo);
        return intval(substr($ruc, -1)) == $digitoVerificador;
    }
    */
    private function validarRuc($ruc)
    {
        // Validar que el RUC tenga 13 dígitos
        if (!preg_match('/^[0-9]{13}$/', $ruc)) {
            return false;
        }

        // Validar que los últimos 3 dígitos sean "001"
        if (substr($ruc, -3) !== '001') {
            return false;
        }

        // Validar el tercer dígito (tipo de entidad)
        $tercerDigito = intval(substr($ruc, 2, 1));
        if ($tercerDigito < 0 || $tercerDigito > 6 && $tercerDigito != 9) {
            return false;
        }

        // Validar los primeros 10 dígitos como cédula
        if (!$this->validarCedulaNew(substr($ruc, 0, 10))) {
            return false;
        }

        return true;
    }

    private function validarCedulaNew($cedula)
    {
        // Validar que la cédula tenga 10 dígitos
        if (!preg_match('/^[0-9]{10}$/', $cedula)) {
            return false;
        }

        // Validar el tercer dígito (debe estar entre 0 y 5 para personas naturales)
        $tercerDigito = intval(substr($cedula, 2, 1));
        if ($tercerDigito < 0 || $tercerDigito > 5) {
            return false;
        }

        // Algoritmo de validación de cédulas
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $total = 0;
        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]) * $coeficientes[$i];
            $total += $valor > 9 ? $valor - 9 : $valor;
        }
        $digitoVerificador = 10 - ($total % 10);
        $digitoVerificador = $digitoVerificador == 10 ? 0 : $digitoVerificador;

        return intval($cedula[9]) == $digitoVerificador;
    }


    private function validarCedula($cedula)
    {
        // Validar que la cédula tenga 10 dígitos
        if (!preg_match('/^[0-9]{10}$/', $cedula)) {
            return false;
        }

        // Validar código de provincia (primeros dos dígitos)
        $provincia = intval(substr($cedula, 0, 2));
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        // Validar tercer dígito (debe ser menor a 6)
        if (intval(substr($cedula, 2, 1)) > 6) {
            return false;
        }

        // Validar dígito verificador
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        for ($i = 0; $i < count($coeficientes); $i++) {
            $producto = intval(substr($cedula, $i, 1)) * $coeficientes[$i];
            $suma += $producto >= 10 ? $producto - 9 : $producto;
        }
        $residuo = $suma % 10;
        $digitoVerificador = $residuo == 0 ? 0 : 10 - $residuo;

        return intval(substr($cedula, -1)) == $digitoVerificador;
    }

    private function validarPasaporte($pasaporte)
    {
        // Para pasaportes solo validamos que tenga al menos 3 caracteres
        // y no exceda de 20 caracteres
        return strlen($pasaporte) >= 3 && strlen($pasaporte) <= 20;
    }

    private function validarConsumidorFinal($consumidorFinal)
    {
        // El consumidor final debe ser exactamente "9999999999999"
        return $consumidorFinal === "9999999999999";
    }

    public function message()
    {
        switch ($this->tipoIdentificacion) {
            case '04':
                return 'El RUC ingresado no es válido.';
            case '05':
                return 'La cédula ingresada no es válida.';
            case '06':
                return 'El pasaporte ingresado no es válido.';
            case '07':
                return 'La identificación de consumidor final debe ser 9999999999999.';
            case '08':
                return 'La identificación del exterior no es válida.';
            default:
                return 'La identificación ingresada no es válida.';
        }
    }
}
