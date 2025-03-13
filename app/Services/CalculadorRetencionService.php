<?php

namespace App\Services;

use App\Models\Retencion;
use App\Models\RetencionDetalle;
use App\Exceptions\RetencionException;

class CalculadorRetencionService
{
    /**
     * Calcula los totales de una retención
     */
    public function calcularTotales(Retencion $retencion): array
    {
        // Obtener detalles de la retención
        $detalles = $retencion->detalles;

        if ($detalles->isEmpty()) {
            throw new RetencionException('La retención debe tener al menos un detalle');
        }

        // Inicializar variables para los totales
        $totalRetenido = 0;
        $totalBaseImponible = 0;
        $totalesPorImpuesto = [];

        // Calcular totales por cada detalle
        foreach ($detalles as $detalle) {
            // Recalcular valor retenido para asegurar precisión
            $valorRetenido = $this->calcularValorRetenido(
                $detalle->base_imponible,
                $detalle->porcentaje_retener
            );

            // Validar que el valor calculado coincida con el almacenado
            if (abs($valorRetenido - $detalle->valor_retenido) > 0.01) {
                throw new RetencionException(
                    "Error en cálculo del valor retenido en la línea {$detalle->linea}"
                );
            }

            // Acumular totales
            $totalRetenido += $valorRetenido;
            $totalBaseImponible += $detalle->base_imponible;

            // Agrupar por tipo de impuesto
            $tipoImpuesto = $detalle->codigoRetencion->tipo_impuesto;
            if (!isset($totalesPorImpuesto[$tipoImpuesto])) {
                $totalesPorImpuesto[$tipoImpuesto] = [
                    'base_imponible' => 0,
                    'valor_retenido' => 0
                ];
            }

            $totalesPorImpuesto[$tipoImpuesto]['base_imponible'] += $detalle->base_imponible;
            $totalesPorImpuesto[$tipoImpuesto]['valor_retenido'] += $valorRetenido;
        }

        // Retornar array con todos los totales calculados
        return [
            'total_retenido' => round($totalRetenido, 2),
            'total_base_imponible' => round($totalBaseImponible, 2),
            'totales_por_impuesto' => $totalesPorImpuesto
        ];
    }

    /**
     * Calcula el valor retenido para un detalle
     */
    public function calcularValorRetenido(float $baseImponible, float $porcentaje): float
    {
        return round(($baseImponible * $porcentaje) / 100, 2);
    }

    /**
     * Valida los cálculos de un detalle de retención
     */
    public function validarCalculosDetalle(RetencionDetalle $detalle): bool
    {
        $valorCalculado = $this->calcularValorRetenido(
            $detalle->base_imponible,
            $detalle->porcentaje_retener
        );

        if (abs($valorCalculado - $detalle->valor_retenido) > 0.01) {
            throw new RetencionException(
                "El valor retenido no coincide con el calculado. " .
                "Esperado: {$valorCalculado}, " .
                "Recibido: {$detalle->valor_retenido}"
            );
        }

        return true;
    }

    /**
     * Calcula totales para dividendos (si aplica)
     */
    public function calcularTotalesDividendos(RetencionDetalle $detalle): array
    {
        if (!$detalle->utilidad_antes_ir) {
            return [];
        }

        $utilidadEfectiva = $detalle->utilidad_antes_ir - $detalle->impuesto_renta_sociedad;

        return [
            'utilidad_antes_ir' => $detalle->utilidad_antes_ir,
            'impuesto_renta_sociedad' => $detalle->impuesto_renta_sociedad,
            'utilidad_efectiva' => $utilidadEfectiva,
            'base_imponible' => $detalle->base_imponible,
            'valor_retenido' => $this->calcularValorRetenido(
                $detalle->base_imponible,
                $detalle->porcentaje_retener
            )
        ];
    }
}
