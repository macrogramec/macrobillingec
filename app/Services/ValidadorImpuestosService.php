<?php

namespace App\Services;

use App\Models\{TipoImpuesto, TarifaImpuesto};
use App\Exceptions\{
    ImpuestoInvalidoException
};
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ValidadorImpuestosService
{
    /**
     * Definición de impuestos requeridos por tipo de producto
     */
    private const IMPUESTOS_REQUERIDOS = [
        'NORMAL' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'MEDICINAS' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'CANASTA_BASICA' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'SERVICIOS_BASICOS' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'SERVICIOS_PROFESIONALES' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'EDUCACION' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'REGIMEN_SIMPLIFICADO' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'ESPECIAL' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
            ['codigo_sri' => '3', 'tarifas' => ['3011', '3023', '3051']], // ICE según el tipo
        ],
        'EXPORTACION' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'SERVICIOS_TURISMO' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'SERVICIOS_TECNICOS' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'AGRICULTURA' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'LIBROS' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'PERIODOS_PERIODICOS' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'TRANSPORTE_PASAJEROS' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'TRANSPORTE_CARGA' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'ARRENDAMIENTO_INMUEBLES' => [
            ['codigo_sri' => '2', 'tarifas' => ['0']], // IVA 0%
        ],
        'ARRENDAMIENTO_COMERCIAL' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'SERVICIOS_CONSTRUCCION' => [
            ['codigo_sri' => '2', 'tarifas' => ['4']], // IVA 15%
        ],
        'NO_OBJETO_IMPUESTO' => [
            ['codigo_sri' => '2', 'tarifas' => ['6']], // No objeto de impuesto
        ],
        'EXENTO_IVA' => [
            ['codigo_sri' => '2', 'tarifas' => ['7']], // Exento de IVA
        ],
    ];

    /**
     * Definición de impuestos prohibidos por tipo de producto
     */
    private const IMPUESTOS_PROHIBIDOS = [
        'NORMAL' => ['03', '05'], // No permite ICE ni IRBPNR
        'MEDICINAS' => ['03', '05'], // No permite ICE ni IRBPNR
        'SERVICIOS_BASICOS' => ['03', '05'], // No permite ICE ni IRBPNR
        'SERVICIOS_PROFESIONALES' => ['03', '05'], // No permite ICE ni IRBPNR
        'EDUCACION' => ['03', '05'], // No permite ICE ni IRBPNR
        'CANASTA_BASICA' => ['03', '05'] // No permite ICE ni IRBPNR
    ];

    /**
     * Valida la estructura básica de los impuestos
     */
    public function validarEstructuraImpuestos(array $impuestos): void
    {
        foreach ($impuestos as $impuesto) {

            if (!$this->validarCamposRequeridos($impuesto)) {
                throw new ImpuestoInvalidoException(
                    "Estructura de impuesto inválida. Campos requeridos: codigo, codigo_porcentaje, base_imponible, valor"
                );
            }

            // Validar que el código exista en tipos_impuestos y esté activo
            $tipoImpuesto = TipoImpuesto::where('codigo_sri', $impuesto['codigo'])
                ->where('activo', true)
                ->first();

            if (!$tipoImpuesto) {
                throw new ImpuestoInvalidoException(
                    "Código de impuesto {$impuesto['codigo']} no válido o inactivo"
                );
            }

            // Validar que la tarifa exista, corresponda al tipo de impuesto y esté activa
            $tarifaValida = TarifaImpuesto::where('tipo_impuesto_codigo', $tipoImpuesto->codigo)
                ->where('codigo_sri', $impuesto['codigo_porcentaje'])
                ->where('activo', true)
                ->first();

            if (!$tarifaValida) {
                throw new ImpuestoInvalidoException(
                    "Tarifa {$impuesto['codigo_porcentaje']} no válida para el impuesto {$impuesto['codigo']}"
                );
            }

            // Validar que la tarifa esté vigente
            if (!$this->tarifaEstaVigente($tarifaValida)) {
                throw new ImpuestoInvalidoException(
                    "La tarifa {$impuesto['codigo_porcentaje']} no está vigente"
                );
            }
        }
    }

    /**
     * Valida la coherencia de los impuestos según el tipo de producto
     */
    public function validarCoherenciaImpuestos(string $tipoProducto, array $impuestos): void
    {
        if (!isset(self::IMPUESTOS_REQUERIDOS[$tipoProducto])) {
            throw new ImpuestoInvalidoException(
                "Tipo de producto no válido: {$tipoProducto}"
            );
        }

        $impuestosRequeridos = self::IMPUESTOS_REQUERIDOS[$tipoProducto];
        $impuestosProhibidos = self::IMPUESTOS_PROHIBIDOS[$tipoProducto] ?? [];

        // Validar impuestos requeridos
        foreach ($impuestosRequeridos as $requerido) {
            if (!$this->tieneImpuestoRequerido($impuestos, $requerido)) {
                throw new ImpuestoInvalidoException(
                    "Falta impuesto requerido {$requerido['codigo_sri']} para el tipo de producto {$tipoProducto}"
                );
            }
        }

        // Validar impuestos prohibidos
        foreach ($impuestos as $impuesto) {
            if (in_array($impuesto['codigo'], $impuestosProhibidos)) {
                throw new ImpuestoInvalidoException(
                    "Impuesto {$impuesto['codigo']} no permitido para el tipo de producto {$tipoProducto}"
                );
            }
        }
    }

    /**
     * Valida los cálculos de los impuestos
     */
    public function validarCalculosImpuestos(array $impuestos, float $subtotal, float $descuento): void
    {
        $baseImponible = $subtotal - $descuento;

        foreach ($impuestos as $impuesto) {
            if ($impuesto['base_imponible'] > $baseImponible) {
                throw new ImpuestoInvalidoException(
                    "Base imponible del impuesto no puede ser mayor al subtotal menos descuento"
                );
            }

            $tipoImpuesto = TipoImpuesto::where('codigo_sri', $impuesto['codigo'])
                ->where('activo', true)
                ->first();

            $tarifa = TarifaImpuesto::where('tipo_impuesto_codigo', $tipoImpuesto->codigo)
                ->where('codigo_sri', $impuesto['codigo_porcentaje'])
                ->where('activo', true)
                ->first();

            $valorCalculado = $this->calcularValorImpuesto($tarifa, $impuesto['base_imponible']);

            if (abs($valorCalculado - $impuesto['valor']) > 0.01) {
                throw new ImpuestoInvalidoException(
                    "Valor calculado ({$valorCalculado}) no coincide con el valor declarado ({$impuesto['valor']})"
                );
            }
        }
    }

    /**
     * Verifica que una tarifa esté vigente
     */
    protected function tarifaEstaVigente(TarifaImpuesto $tarifa): bool
    {
        $fechaActual = Carbon::now();

        return $tarifa->fecha_inicio <= $fechaActual &&
            ($tarifa->fecha_fin === null || $tarifa->fecha_fin >= $fechaActual);
    }

    /**
     * Valida los campos requeridos de un impuesto
     */
    protected function validarCamposRequeridos(array $impuesto): bool
    {
        $camposRequeridos = ['codigo', 'codigo_porcentaje', 'base_imponible', 'valor'];

        foreach ($camposRequeridos as $campo) {
            if (!isset($impuesto[$campo])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si el conjunto de impuestos incluye uno requerido
     */
    protected function tieneImpuestoRequerido(array $impuestos, array $requerido): bool
    {
        foreach ($impuestos as $impuesto) {
            $codigoNormalizado = ltrim($impuesto['codigo'], '0');
            if ($codigoNormalizado === $requerido['codigo_sri'] &&
                in_array($impuesto['codigo_porcentaje'], $requerido['tarifas'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calcula el valor del impuesto según el tipo de cálculo de la tarifa
     */
    protected function calcularValorImpuesto(TarifaImpuesto $tarifa, float $baseImponible): float
    {
        switch ($tarifa->tipo_calculo) {
            case 'PORCENTAJE':
                return round(($baseImponible * $tarifa->porcentaje) / 100, 2);
            case 'ESPECIFICO':
                return round($tarifa->valor_especifico, 2);
            case 'MIXTO':
                $valorPorcentaje = ($baseImponible * $tarifa->porcentaje) / 100;
                return round($valorPorcentaje + $tarifa->valor_especifico, 2);
            default:
                throw new ImpuestoInvalidoException("Tipo de cálculo no soportado: {$tarifa->tipo_calculo}");
        }
    }

    /**
     * Obtiene los impuestos requeridos para un tipo de producto
     */
    public function obtenerImpuestosRequeridos(string $tipoProducto): array
    {
        return self::IMPUESTOS_REQUERIDOS[$tipoProducto] ?? [];
    }

    /**
     * Obtiene los impuestos prohibidos para un tipo de producto
     */
    public function obtenerImpuestosProhibidos(string $tipoProducto): array
    {
        return self::IMPUESTOS_PROHIBIDOS[$tipoProducto] ?? [];
    }
}
