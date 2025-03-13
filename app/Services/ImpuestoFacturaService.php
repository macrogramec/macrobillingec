<?php

namespace App\Services;

use App\Models\{Factura, FacturaDetalle, FacturaImpuesto, FacturaDetalleImpuesto, TarifaImpuesto};
use App\Exceptions\{ImpuestoFacturaException, CalculoImpuestoException};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImpuestoFacturaService
{
    protected $calculadorImpuestos;
    protected $validadorImpuestos;

    public function __construct(
        CalculadorImpuestosService $calculadorImpuestos,
        ValidadorImpuestosService $validadorImpuestos
    ) {
        $this->calculadorImpuestos = $calculadorImpuestos;
        $this->validadorImpuestos = $validadorImpuestos;
    }

    /**
     * Procesar y guardar impuestos de una factura
     */
    public function procesarImpuestosFactura(Factura $factura, array $detalles): array
    {
        return DB::transaction(function () use ($factura, $detalles) {
            // Procesar impuestos por detalle
            $impuestosDetalles = [];
            $totalesGenerales = [
                'subtotal' => 0,
                'descuento' => 0,
                'porcentaje' => 0,
                'impuestos' => [],
                'total_impuesto' => 0,
                'total' => 0
            ];

            foreach ($detalles as $index => $detalle) {
                $resultadoDetalle = $this->procesarImpuestosDetalle($factura, $detalle, $index);
                $impuestosDetalles[] = $resultadoDetalle;
              //  dd($resultadoDetalle['impuestos']);
                // Acumular totales
                $totalesGenerales['subtotal'] += $resultadoDetalle['subtotal'];
                $totalesGenerales['descuento'] += $resultadoDetalle['descuento'];

                foreach ($resultadoDetalle['impuestos'] as $impuesto) {
                    $key = $impuesto['codigo'] . '-' . $impuesto['codigo_porcentaje'];
                    if (!isset($totalesGenerales['impuestos'][$key])) {
                        $totalesGenerales['impuestos'][$key] = [
                            'tipo_impuesto_codigo' => $impuesto['codigo'],
                            'tarifa_codigo' => $impuesto['codigo_porcentaje'],
                            'tarifa' => $impuesto['impuesto_tarifa'],
                            'base_imponible' => 0,
                            'valor' => 0
                        ];
                    }
                    $totalesGenerales['impuestos'][$key]['base_imponible'] += $impuesto['base_imponible'];
                    $totalesGenerales['impuestos'][$key]['valor'] += $impuesto['valor'];
                }
            }
            //dd($totalesGenerales);
            // Guardar totales de impuestos en la factura
            foreach ($totalesGenerales['impuestos'] as $impuestoTotal) {
                FacturaImpuesto::create([
                    'factura_id' => $factura->id,
                    'tipo_impuesto_codigo' => $impuestoTotal['tipo_impuesto_codigo'],
                    'tarifa_codigo' => $impuestoTotal['tarifa_codigo'],
                    'porcentaje' => $impuestoTotal['tarifa'],
                    'base_imponible' => $impuestoTotal['base_imponible'],
                    'valor' => $impuestoTotal['valor']
                ]);
                $totalesGenerales['total_impuesto'] += $impuestoTotal['valor'];
            }

            $totalesGenerales['total'] = $totalesGenerales['subtotal']
                - $totalesGenerales['descuento']
                + collect($totalesGenerales['impuestos'])->sum('valor');

            return [
                'detalles' => $impuestosDetalles,
                'totales' => $totalesGenerales
            ];
        });
    }

    /**
     * Procesar impuestos de un detalle de factura
     */
    protected function procesarImpuestosDetalle(Factura $factura, array $detalle, int $index): array
    {
        // Validar la estructura de los impuestos
        $this->validadorImpuestos->validarEstructuraImpuestos($detalle['impuestos']);

        // Validar coherencia según tipo de producto
        $this->validadorImpuestos->validarCoherenciaImpuestos(
            $detalle['tipo_producto'] ?? 'NORMAL',
            $detalle['impuestos']
        );

        // Calcular base imponible y valores
        $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
        $descuento = $detalle['descuento'] ?? 0;
        $baseImponible = $subtotal - $descuento;

        // Crear el detalle de la factura
        $facturaDetalle = FacturaDetalle::create([
            'factura_id' => $factura->id,
            'linea' => $index + 1,
            'codigoPrincipal' => $detalle['codigo_principal'] ?? null,
            'codigoAuxiliar' => $detalle['codigo_auxiliar'] ?? null,
            'descripcion' => $detalle['descripcion'],
            'cantidad' => $detalle['cantidad'],
            'precioUnitario' => $detalle['precio_unitario'],
            'descuento' => $detalle['descuento'] ?? 0,
            'precioTotalSinImpuesto' => $baseImponible,
            'impuesto_codigo' => $detalle['impuestos'][0]['codigo'] ?? '02',
            'impuesto_codigoPorcentaje' => $detalle['impuestos'][0]['codigo_porcentaje'] ?? '2',
            'impuesto_tarifa' => $detalle['impuestos'][0]['impuesto_tarifa'],
            'impuesto_baseImponible' => $detalle['impuestos'][0]['base_imponible'],
            'impuesto_valor' => $detalle['impuestos'][0]['valor'],
            'version' => '1.0.0',
            'detallesAdicionales' => isset($detalle['detalles_adicionales']) ?
                json_encode($detalle['detalles_adicionales']) : null,
            'unidadMedida' => $detalle['unidad_medida'] ?? null,
            'precioUnitarioSubsidio' => $detalle['precio_unitario_subsidio'] ?? null,
        ]);
        // Crear los registros en factura_detalle_impuestos
        foreach ($detalle['impuestos'] as $impuesto) {
            FacturaDetalleImpuesto::create([
                'factura_detalle_id' => $facturaDetalle->id,
                'codigo' => $impuesto['codigo']  ?? '02',
                'codigo_porcentaje' => $impuesto['codigo_porcentaje']  ?? '2',
                'tarifa' => $detalle['impuestos'][0]['impuesto_tarifa'] ?? '15.00',
                'base_imponible' => $impuesto['base_imponible'],
                'valor' => $impuesto['valor'],
                'version' => '1.0.0'
            ]);
        }

        return [
            'detalle_id' => $facturaDetalle->id,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'base_imponible' => $baseImponible,
            'impuestos' => $detalle['impuestos'],
            'total' => $baseImponible + collect($detalle['impuestos'])->sum('valor')
        ];
    }

    /**
     * Validar totales de impuestos declarados
     */
    public function validarTotalesDeclarados(array $detalles, array $totalesDeclarados): bool
    {
        $totalesCalculados = $this->calcularTotales($detalles);

        // Validar subtotal
        if (abs($totalesDeclarados['subtotal'] - $totalesCalculados['subtotal']) > 0.01) {
            throw new CalculoImpuestoException("Subtotal declarado no coincide con el calculado");
        }

        // Validar descuento
        if (abs($totalesDeclarados['descuento'] - $totalesCalculados['descuento']) > 0.01) {
            throw new CalculoImpuestoException("Descuento declarado no coincide con el calculado");
        }

        // Validar impuestos
        foreach ($totalesDeclarados['impuestos'] as $impuestoDeclarado) {
            $key = $impuestoDeclarado['tipo_impuesto_codigo'] . '-' . $impuestoDeclarado['tarifa_codigo'];

            if (!isset($totalesCalculados['impuestos'][$key])) {
                throw new CalculoImpuestoException("Impuesto declarado no encontrado en los cálculos");
            }

            $impuestoCalculado = $totalesCalculados['impuestos'][$key];

            if (abs($impuestoDeclarado['base_imponible'] - $impuestoCalculado['base_imponible']) > 0.01) {
                throw new CalculoImpuestoException("Base imponible declarada no coincide con la calculada");
            }

            if (abs($impuestoDeclarado['valor'] - $impuestoCalculado['valor']) > 0.01) {
                throw new CalculoImpuestoException("Valor de impuesto declarado no coincide con el calculado");
            }
        }

        return true;
    }

    /**
     * Calcular totales de una factura
     */
    protected function calcularTotales(array $detalles): array
    {
        $totales = [
            'subtotal' => 0,
            'descuento' => 0,
            'impuestos' => [],
            'total' => 0
        ];

        foreach ($detalles as $detalle) {
            $calculosDetalle = $this->calculadorImpuestos->calcularImpuestosDetalle($detalle);

            $totales['subtotal'] += $calculosDetalle['base_imponible'];
            $totales['descuento'] += $detalle['descuento'] ?? 0;

            foreach ($calculosDetalle['impuestos'] as $impuesto) {
                $key = $impuesto['tipo_impuesto_codigo'] . '-' . $impuesto['tarifa_codigo'];

                if (!isset($totales['impuestos'][$key])) {
                    $totales['impuestos'][$key] = [
                        'tipo_impuesto_codigo' => $impuesto['tipo_impuesto_codigo'],
                        'tarifa_codigo' => $impuesto['tarifa_codigo'],
                        'base_imponible' => 0,
                        'valor' => 0
                    ];
                }

                $totales['impuestos'][$key]['base_imponible'] += $impuesto['base_imponible'];
                $totales['impuestos'][$key]['valor'] += $impuesto['valor'];
            }
        }

        $totales['total'] = $totales['subtotal']
            - $totales['descuento']
            + collect($totales['impuestos'])->sum('valor');

        return $totales;
    }
}
