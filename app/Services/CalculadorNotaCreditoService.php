<?php

namespace App\Services;

use App\Models\{NotaCredito, Factura};
use App\Exceptions\NotaCreditoException;

class CalculadorNotaCreditoService
{
    /**
     * Calcula los totales de una nota de crédito
     */
    public function calcularTotales($notaCredito): array
    {
        $totales = [
            'subtotal' => 0,
            'total_sin_impuestos' => 0,
            'total_descuento' => 0,
            'total_ice' => 0,
            'total_iva' => 0,
            'total_irbpnr' => 0,
            'total_impuestos' => 0,
            'valor_total' => 0
        ];

        foreach ($notaCredito as $detalle) {
            // Calcular subtotal por detalle
           // dd($detalle);
            $subtotalDetalle = $detalle['cantidad'] * $detalle['precio_unitario'];
            $totales['subtotal'] += $subtotalDetalle;

            // Sumar descuentos
            $totales['total_descuento'] += $detalle['descuento'];

            // Base imponible después de descuentos
            $baseImponible = $subtotalDetalle - $detalle['descuento'];
            $totales['total_sin_impuestos'] += $baseImponible;

            // Procesar impuestos
            foreach ($detalle['impuestos'] as $impuesto) {

                switch ($impuesto['codigo']) {
                    case '2': // IVA
                        $totales['total_iva'] += $impuesto['valor'];
                        break;
                    case '3': // ICE
                        $totales['total_ice'] += $impuesto['valor'];
                        break;
                    case '5': // IRBPNR
                        $totales['total_irbpnr'] += $impuesto['valor'];
                        break;
                }
                $totales['total_impuestos'] += $impuesto['valor'];
            }
        }

        // Calcular valor total
        $totales['valor_total'] = $totales['total_sin_impuestos'] + $totales['total_impuestos'];

        // Redondear todos los valores a 2 decimales
        return array_map(function($valor) {
            return round($valor, 2);
        }, $totales);
    }

    /**
     * Calcula los totales de una factura para comparación
     */
    public function calcularTotalFactura(Factura $factura): float
    {
        return $factura->total;
    }

    /**
     * Calcula el total de los detalles para nota de crédito
     */
    public function calcularTotalDetalles(array $detalles): float
    {
        $total = 0;

        foreach ($detalles as $detalle) {
            $subtotal = $detalle['cantidad_devuelta'] * $detalle['precio_unitario'];
            $descuento = $detalle['descuento'] ?? 0;
            $baseImponible = $subtotal - $descuento;

            // Sumar impuestos
            $impuestos = 0;
            foreach ($detalle['impuestos'] ?? [] as $impuesto) {
                $impuestos += $impuesto['valor'];
            }

            $total += $baseImponible + $impuestos;
        }

        return round($total, 2);
    }

    /**
     * Calcula los valores proporcionales para una devolución parcial
     */
    public function calcularValoresProporcionales(
        float $cantidadOriginal,
        float $cantidadDevuelta,
        array $valoresOriginales
    ): array {
        if ($cantidadOriginal <= 0) {
            throw new NotaCreditoException("La cantidad original debe ser mayor a 0");
        }

        $proporcion = $cantidadDevuelta / $cantidadOriginal;
        $valoresProporcionales = [];

        foreach ($valoresOriginales as $clave => $valor) {
            $valoresProporcionales[$clave] = round($valor * $proporcion, 2);
        }

        return $valoresProporcionales;
    }

    /**
     * Calcula el IVA 15% según normativa 2024
     */
    public function calcularIVA(float $baseImponible, string $tipoProducto = 'NORMAL'): float
    {
        // Obtener la tarifa según el tipo de producto
        $tarifa = $this->obtenerTarifaIVA($tipoProducto);

        // Calcular el IVA
        $iva = ($baseImponible * $tarifa) / 100;

        return round($iva, 2);
    }

    /**
     * Obtiene la tarifa de IVA según el tipo de producto
     */
    protected function obtenerTarifaIVA(string $tipoProducto): float
    {
        return match($tipoProducto) {
            'NORMAL' => 15.00,      // Tarifa general 2024
            'TURISMO' => 8.00,      // Servicios turísticos
            'CONSTRUCCION' => 5.00,  // Materiales y servicios de construcción
            default => 0.00         // Productos/servicios con tarifa 0%
        };
    }

    /**
     * Calcula el ICE según el tipo de producto
     */
    public function calcularICE(float $baseImponible, string $codigoICE): float
    {
        $tarifas = [
            '3011' => 150.00, // Productos del tabaco
            '3023' => 75.00,  // Bebidas alcohólicas
            '3051' => 35.00,  // Vehículos > USD 70,000
            '3072' => 0.10,   // Fundas plásticas
            // Otras tarifas ICE...
        ];

        if (!isset($tarifas[$codigoICE])) {
            throw new NotaCreditoException("Código ICE no válido: {$codigoICE}");
        }

        $ice = ($baseImponible * $tarifas[$codigoICE]) / 100;
        return round($ice, 2);
    }

    /**
     * Calcula saldos disponibles para devolución
     */
    public function calcularSaldosDisponibles(Factura $factura): array
    {
        $saldos = [];

        foreach ($factura->detalles as $detalle) {
            $saldos[$detalle->id] = [
                'cantidad_original' => $detalle->cantidad,
                'cantidad_devuelta' => $detalle->cantidad - $detalle->cantidad_disponible,
                'cantidad_disponible' => $detalle->cantidad_disponible,
                'valor_original' => $detalle->precio_total_sin_impuesto,
                'valor_devuelto' => $detalle->precio_total_sin_impuesto -
                    ($detalle->cantidad_disponible * $detalle->precio_unitario),
                'valor_disponible' => $detalle->cantidad_disponible * $detalle->precio_unitario,
                'impuestos' => $this->calcularSaldosImpuestos($detalle)
            ];
        }

        return $saldos;
    }

    /**
     * Calcula saldos de impuestos por detalle
     */
    protected function calcularSaldosImpuestos($detalle): array
    {
        $saldosImpuestos = [];

        foreach ($detalle->impuestos as $impuesto) {
            $proporcionDisponible = $detalle->cantidad_disponible / $detalle->cantidad;

            $saldosImpuestos[$impuesto->codigo] = [
                'valor_original' => $impuesto->valor,
                'valor_devuelto' => $impuesto->valor * (1 - $proporcionDisponible),
                'valor_disponible' => $impuesto->valor * $proporcionDisponible
            ];
        }

        return $saldosImpuestos;
    }
}
