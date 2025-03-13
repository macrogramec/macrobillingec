<?php

namespace App\Services;

use App\Models\{NotaCredito, Factura, FacturaDetalle};
use App\Exceptions\NotaCreditoException;
use Carbon\Carbon;

class ValidadorNotaCreditoService
{
    protected $calculadorService;

    public function __construct(CalculadorNotaCreditoService $calculadorService)
    {
        $this->calculadorService = $calculadorService;
    }

    /**
     * Valida los datos básicos de una nota de crédito
     */
    public function validarDatosBasicos(array $datos): void
    {
        // Validar ambiente
        if (!in_array($datos['ambiente'], ['1', '2'])) {
            throw new NotaCreditoException('El ambiente debe ser 1 (Pruebas) o 2 (Producción)');
        }

        // Validar tipo de emisión
        if ($datos['tipo_emision'] !== '1') {
            throw new NotaCreditoException('El tipo de emisión debe ser 1 (Normal)');
        }

        // Validar fecha de emisión
        $fechaEmision = Carbon::parse($datos['fecha_emision']);
        if ($fechaEmision->isAfter(now())) {
            throw new NotaCreditoException('La fecha de emisión no puede ser futura');
        }

        // Validar documento modificado
        if (!isset($datos['doc_modificado'])) {
            throw new NotaCreditoException('Se requiere información del documento modificado');
        }

        // Validar que la fecha del documento modificado sea anterior
        $fechaDocModificado = Carbon::parse($datos['doc_modificado']['fecha_emision']);
        if ($fechaDocModificado->isAfter($fechaEmision)) {
            throw new NotaCreditoException('La fecha del documento modificado debe ser anterior a la nota de crédito');
        }

        // Validar motivo
        if (empty($datos['doc_modificado']['motivo'])) {
            throw new NotaCreditoException('El motivo de la nota de crédito es requerido');
        }

        // Validar detalles
        if (empty($datos['detalles'])) {
            throw new NotaCreditoException('La nota de crédito debe tener al menos un detalle');
        }
    }

    /**
     * Valida si una factura puede tener nota de crédito
     */
    public function validarFacturaParaNC(Factura $factura, string $tipoAplicacion): void
    {
        // Validar estado de la factura
        if ($factura->estado !== 'AUTORIZADA') {
            throw new NotaCreditoException('Solo se pueden emitir notas de crédito para facturas autorizadas');
        }

        // Validar plazo máximo (90 días según normativa SRI)
        $fechaMaxima = $factura->fecha_emision->addDays(90);
        if (now()->isAfter($fechaMaxima)) {
            throw new NotaCreditoException('Ha excedido el plazo máximo para emitir nota de crédito (90 días)');
        }

        // Para NC totales, validar que no existan NC parciales previas
        if ($tipoAplicacion === 'TOTAL') {
            $tieneNCParciales = $factura->notasCredito()
                ->where('estado', '!=', 'ANULADA')
                ->exists();

            if ($tieneNCParciales) {
                throw new NotaCreditoException('No se puede emitir NC total porque existen NC parciales');
            }
        }

        // Validar que no tenga una NC total previa
        $tieneNCTotal = $factura->notasCredito()
            ->where('tipo_aplicacion', 'TOTAL')
            ->where('estado', '!=', 'ANULADA')
            ->exists();

        if ($tieneNCTotal) {
            throw new NotaCreditoException('La factura ya tiene una nota de crédito total');
        }
    }

    /**
     * Valida la aplicación parcial de una nota de crédito
     */
    public function validarAplicacionParcial(Factura $factura, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            $facturaDetalle = FacturaDetalle::find($detalle['factura_detalle_id']);

            if (!$facturaDetalle) {
                throw new NotaCreditoException('Detalle de factura no encontrado');
            }

            // Validar que el detalle pertenezca a la factura
            if ($facturaDetalle->factura_id !== $factura->id) {
                throw new NotaCreditoException('El detalle no pertenece a la factura indicada');
            }

            // Validar cantidad disponible
            if ($detalle['cantidad_devuelta'] > $facturaDetalle->cantidad_disponible) {
                throw new NotaCreditoException(
                    "La cantidad a devolver ({$detalle['cantidad_devuelta']}) excede la cantidad disponible ({$facturaDetalle->cantidad_disponible}) para el ítem: {$facturaDetalle->descripcion}"
                );
            }

            // Validar cantidad mínima
            if ($detalle['cantidad_devuelta'] <= 0) {
                throw new NotaCreditoException('La cantidad a devolver debe ser mayor a 0');
            }

            // Validaciones de precisión numérica
            if (!$this->validarPrecisionNumerica($detalle['cantidad_devuelta'], 6)) {
                throw new NotaCreditoException('La cantidad debe tener máximo 6 decimales');
            }
        }
    }

    /**
     * Valida la aplicación total de una nota de crédito
     */
    public function validarAplicacionTotal(Factura $factura, array $detalles): void
    {
        // En NC total, todos los detalles deben estar incluidos
        $detallesFactura = $factura->detalles()->pluck('id')->toArray();
        $detallesNC = array_column($detalles, 'factura_detalle_id');

        $diferencia = array_diff($detallesFactura, $detallesNC);
        if (!empty($diferencia)) {
            throw new NotaCreditoException('En una NC total deben incluirse todos los items de la factura');
        }

        foreach ($detalles as $detalle) {
            $facturaDetalle = FacturaDetalle::find($detalle['factura_detalle_id']);

            // Validar que la cantidad sea exactamente igual
            if ($detalle['cantidad_devuelta'] !== $facturaDetalle->cantidad) {
                throw new NotaCreditoException(
                    "Para NC total, la cantidad a devolver debe ser igual a la cantidad original"
                );
            }
        }

        // Validar que los montos coincidan exactamente
        $totalFactura = $this->calculadorService->calcularTotalFactura($factura);
        $totalNC = $this->calculadorService->calcularTotalDetalles($detalles);

        if (abs($totalFactura - $totalNC) > 0.01) {
            throw new NotaCreditoException('Los montos no coinciden con la factura original');
        }
    }

    /**
     * Valida la precisión numérica de un valor
     */
    protected function validarPrecisionNumerica(float $valor, int $decimales): bool
    {
        $partes = explode('.', (string)$valor);
        if (isset($partes[1])) {
            return strlen($partes[1]) <= $decimales;
        }
        return true;
    }

    /**
     * Valida los impuestos de un detalle
     */
    public function validarImpuestos(array $impuestos, float $baseImponible): void
    {
        foreach ($impuestos as $impuesto) {
            // Validar que la base imponible coincida
            if (abs($impuesto['base_imponible'] - $baseImponible) > 0.01) {
                throw new NotaCreditoException('La base imponible del impuesto no coincide');
            }

            // Validar cálculo del impuesto
            $valor = ($impuesto['base_imponible'] * $impuesto['tarifa']) / 100;
            if (abs($valor - $impuesto['valor']) > 0.01) {
                throw new NotaCreditoException('El valor del impuesto está mal calculado');
            }

            // Validar precisión de valores monetarios
            if (!$this->validarPrecisionNumerica($impuesto['base_imponible'], 2) ||
                !$this->validarPrecisionNumerica($impuesto['valor'], 2)) {
                throw new NotaCreditoException('Los valores monetarios deben tener máximo 2 decimales');
            }
        }
    }
}
