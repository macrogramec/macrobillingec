<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ClaveAccesoGenerator
{
    /**
     * Longitud esperada de la clave de acceso
     */
    private const LONGITUD_CLAVE = 49;

    /**
     * Factores para el cálculo del dígito verificador
     */
    private const FACTORES = [2, 3, 4, 5, 6, 7];

    /**
     * Genera la clave de acceso para un documento electrónico
     */
    public function generate(array $datos): string
    {
        // Obtener fecha en formato requerido
        $fecha = isset($datos['fecha_emision'])
            ? Carbon::parse($datos['fecha_emision'])
            : Carbon::now();

        // Formatear componentes usando array de formateo
        $componentes = [
            $fecha->format('dmY'),
            str_pad($datos['tipo_comprobante'] ?? '01', 2, '0', STR_PAD_LEFT),
            str_pad($datos['ruc'], 13, '0', STR_PAD_LEFT),
            str_pad($datos['tipo_ambiente'], 1, '0', STR_PAD_LEFT),
            str_pad($datos['establecimiento'], 3, '0', STR_PAD_LEFT),
            str_pad($datos['punto_emision'], 3, '0', STR_PAD_LEFT),
            str_pad($datos['secuencial'], 9, '0', STR_PAD_LEFT),
            '12345678', // Código numérico fijo
            '1'         // Tipo emisión normal
        ];

        // Concatenar para obtener clave sin dígito verificador
        $clave = implode('', $componentes);

        // Agregar dígito verificador
        return $clave . $this->calcularDigitoVerificador($clave);
    }

    /**
     * Calcula el dígito verificador usando el algoritmo módulo 11
     */
    protected function calcularDigitoVerificador(string $clave): string
    {
        $suma = 0;
        $longitud = strlen($clave);
        $factoresCount = count(self::FACTORES);

        // Usar multiplicación directa en lugar de módulo para mejorar rendimiento
        for ($i = 0; $i < $longitud; $i++) {
            $factor = self::FACTORES[$i % $factoresCount];
            $suma += intval($clave[$longitud - $i - 1]) * $factor;
        }

        $residuo = $suma % 11;
        $digitoVerificador = 11 - $residuo;

        if ($digitoVerificador === 11) {
            return '0';
        }

        if ($digitoVerificador === 10) {
            return '1';
        }

        return (string) $digitoVerificador;
    }

    /**
     * Valida si una clave de acceso es válida
     */
    public function validar(string $claveAcceso): bool
    {
        // Cache de resultados de validación para claves frecuentes
        return Cache::remember("validacion_clave_{$claveAcceso}", 300, function() use ($claveAcceso) {
            // Verificar longitud
            if (strlen($claveAcceso) !== self::LONGITUD_CLAVE) {
                return false;
            }

            // Verificar que sean solo números usando expresión regular optimizada
            if (!preg_match('/^\d{49}$/', $claveAcceso)) {
                return false;
            }

            // Obtener clave sin dígito verificador
            $clave = substr($claveAcceso, 0, -1);
            $digitoVerificador = substr($claveAcceso, -1);

            // Calcular y comparar dígito verificador
            return $this->calcularDigitoVerificador($clave) === $digitoVerificador;
        });
    }

    /**
     * Extrae la información de una clave de acceso
     */
    public function decodificar(string $claveAcceso): array
    {
        // Cache de decodificación para claves frecuentes
        return Cache::remember("decodificacion_clave_{$claveAcceso}", 300, function() use ($claveAcceso) {
            if (!$this->validar($claveAcceso)) {
                throw new \InvalidArgumentException('Clave de acceso inválida');
            }

            // Extraer componentes usando array de posiciones
            $posiciones = [
                'fecha_emision' => [0, 8],
                'tipo_comprobante' => [8, 2],
                'ruc' => [10, 13],
                'ambiente' => [23, 1],
                'establecimiento' => [24, 3],
                'punto_emision' => [27, 3],
                'secuencial' => [30, 9],
                'codigo_numerico' => [39, 8],
                'tipo_emision' => [47, 1],
                'digito_verificador' => [48, 1]
            ];

            $resultado = [];
            foreach ($posiciones as $campo => [$inicio, $longitud]) {
                $valor = substr($claveAcceso, $inicio, $longitud);

                // Convertir fecha si es necesario
                if ($campo === 'fecha_emision') {
                    $valor = Carbon::createFromFormat('dmY', $valor);
                }

                $resultado[$campo] = $valor;
            }

            return $resultado;
        });
    }
}
