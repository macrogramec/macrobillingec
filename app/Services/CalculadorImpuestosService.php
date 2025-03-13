<?php

namespace App\Services;

use App\Models\{TipoImpuesto, TarifaImpuesto};
use App\Exceptions\ImpuestoInvalidoException;
use Illuminate\Support\Collection;

class CalculadorImpuestosService
{
    public function calcularImpuestosDetalle(array $detalle): array
    {
        $baseImponible = $this->calcularBaseImponible($detalle);
        $impuestos = [];

        foreach ($detalle['impuestos'] as $impuesto) {
            $tarifa = TarifaImpuesto::getPorCodigosSRI(
                $impuesto['codigo'],
                $impuesto['codigo_porcentaje']
            );

            if (!$tarifa || !$tarifa->estaVigente()) {
                throw new ImpuestoInvalidoException(
                    "Tarifa no válida o no vigente: {$impuesto['codigo']}-{$impuesto['codigo_porcentaje']}"
                );
            }

            // Validar condiciones especiales si existen
            foreach ($tarifa->condiciones as $condicion) {
                if (!$condicion->validarCondicion($detalle)) {
                    throw new ImpuestoInvalidoException(
                        "No cumple con las condiciones para la tarifa: {$tarifa->descripcion}"
                    );
                }
            }

            $valor = $tarifa->calcularValor($baseImponible);

            $impuestos[] = [
                'tipo_impuesto_codigo' => $tarifa->tipo_impuesto_codigo,
                'tarifa_codigo' => $tarifa->codigo,
                'base_imponible' => $baseImponible,
                'porcentaje' => $tarifa->porcentaje,
                'valor_especifico' => $tarifa->valor_especifico,
                'valor' => $valor
            ];
        }

        return [
            'base_imponible' => $baseImponible,
            'impuestos' => $impuestos,
            'total_impuestos' => collect($impuestos)->sum('valor')
        ];
    }

    // En CalculadorImpuestosService
    public function calcularImpuestosFactura(array $detalles): array
    {
        $totalesImpuestos = [];
        $subtotal = 0;
        $total_impuestos = 0;
        $total_descuentos = 0;
        $total = 0;

        foreach ($detalles as $detalle) {
            // Calcular subtotal por detalle
            $subtotalDetalle = $detalle['cantidad'] * $detalle['precio_unitario'];
            $subtotal += $subtotalDetalle;

            // Acumular descuentos
            $total_descuentos += $detalle['descuento'] ?? 0;

            // Calcular impuestos
            $calculosDetalle = $this->calcularImpuestosDetalle($detalle);
            foreach ($calculosDetalle['impuestos'] as $impuesto) {
                $total_impuestos += $impuesto['valor'];
                $key = $impuesto['tipo_impuesto_codigo'] . '-' . $impuesto['tarifa_codigo'];

                if (!isset($totalesImpuestos[$key])) {
                    $totalesImpuestos[$key] = [
                        'tipo_impuesto_codigo' => $impuesto['tipo_impuesto_codigo'],
                        'tarifa_codigo' => $impuesto['tarifa_codigo'],
                        'base_imponible' => 0,
                        'valor' => 0
                    ];
                }

                $totalesImpuestos[$key]['base_imponible'] += $impuesto['base_imponible'];
                $totalesImpuestos[$key]['valor'] += $impuesto['valor'];
            }
        }

        // Calcular total general
        $total = $subtotal - $total_descuentos + $total_impuestos;

        return [
            'impuestos' => array_values($totalesImpuestos),
            'subtotal' => $subtotal,
            'total_descuento' => $total_descuentos,
            'total_impuestos' => $total_impuestos,
            'total' => $total
        ];
    }

    protected function calcularBaseImponible(array $detalle): float
    {
        $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
        return round($subtotal - ($detalle['descuento'] ?? 0), 2);
    }

    public function validarImpuestosDeclarados(array $detalle, array $impuestosDeclarados): bool
    {
        $calculados = $this->calcularImpuestosDetalle($detalle);

        foreach ($impuestosDeclarados as $impuestoDeclarado) {
            $encontrado = false;
            foreach ($calculados['impuestos'] as $calculado) {
                if ($calculado['tipo_impuesto_codigo'] === $impuestoDeclarado['tipo_impuesto_codigo'] &&
                    $calculado['tarifa_codigo'] === $impuestoDeclarado['tarifa_codigo']) {

                    if (abs($calculado['valor'] - $impuestoDeclarado['valor']) > 0.01) {
                        throw new ImpuestoInvalidoException(
                            "Valor de impuesto declarado no coincide con el calculado"
                        );
                    }
                    $encontrado = true;
                    break;
                }
            }

            if (!$encontrado) {
                throw new ImpuestoInvalidoException(
                    "Impuesto declarado no corresponde a los calculados"
                );
            }
        }

        return true;
    }
}
