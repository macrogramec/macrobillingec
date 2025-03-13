<?php

namespace App\Services;

use App\Exceptions\GuiaRemisionException;
use Carbon\Carbon;

class ValidadorGuiaRemisionService
{
    /**
     * Valida los datos básicos de una guía de remisión
     */
    public function validarDatosBasicos(array $datos): void
    {
        // Validar ambiente
        if (!in_array($datos['ambiente'], ['1', '2'])) {
            throw new GuiaRemisionException('El ambiente debe ser 1 (Pruebas) o 2 (Producción)');
        }

        // Validar tipo de emisión
        if ($datos['tipo_emision'] !== '1') {
            throw new GuiaRemisionException('El tipo de emisión debe ser 1 (Normal)');
        }

        // Validar fechas de transporte
        $fechaIni = Carbon::parse($datos['fecha_ini_transporte']);
        $fechaFin = Carbon::parse($datos['fecha_fin_transporte']);

        if ($fechaIni->isAfter(now())) {
            throw new GuiaRemisionException('La fecha de inicio de transporte no puede ser futura');
        }

        if ($fechaFin->isBefore($fechaIni)) {
            throw new GuiaRemisionException('La fecha de fin de transporte debe ser igual o posterior a la fecha de inicio');
        }

        // Validar datos del transportista
        if (empty($datos['transportista'])) {
            throw new GuiaRemisionException('Los datos del transportista son requeridos');
        }

        // Validar que exista al menos un destinatario
        if (empty($datos['destinatarios'])) {
            throw new GuiaRemisionException('Debe especificar al menos un destinatario');
        }

        // Validar que cada destinatario tenga al menos un detalle
        foreach ($datos['destinatarios'] as $index => $destinatario) {
            if (empty($destinatario['detalles'])) {
                throw new GuiaRemisionException("El destinatario #{$index} debe tener al menos un detalle");
            }
        }
    }

    /**
     * Valida los datos del documento de sustento
     */
    public function validarDocumentoSustento(array $documento): void
    {
        // Validar que existan todos los datos del documento de sustento
        if (isset($documento['tipo']) && empty($documento['numero'])) {
            throw new GuiaRemisionException('El número de documento es requerido cuando se especifica el tipo');
        }

        if (isset($documento['numero']) && !preg_match('/^[0-9]{3}-[0-9]{3}-[0-9]{9}$/', $documento['numero'])) {
            throw new GuiaRemisionException('El número de documento debe tener el formato 001-001-000000001');
        }

        if (isset($documento['fecha_emision'])) {
            $fechaEmision = Carbon::parse($documento['fecha_emision']);
            if ($fechaEmision->isAfter(now())) {
                throw new GuiaRemisionException('La fecha de emisión del documento de sustento no puede ser futura');
            }
        }
    }

    /**
     * Valida los detalles de los productos
     */
    public function validarDetalles(array $detalles): void
    {
        foreach ($detalles as $index => $detalle) {
            // Validar descripción
            if (empty($detalle['descripcion'])) {
                throw new GuiaRemisionException("La descripción del producto #{$index} es requerida");
            }

            // Validar cantidad
            if (!isset($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                throw new GuiaRemisionException("La cantidad del producto #{$index} debe ser mayor a 0");
            }

            // Validar detalles adicionales si existen
            if (isset($detalle['detalles_adicionales'])) {
                foreach ($detalle['detalles_adicionales'] as $idxAdic => $adicional) {
                    if (empty($adicional['nombre']) || empty($adicional['valor'])) {
                        throw new GuiaRemisionException("El nombre y valor del detalle adicional #{$idxAdic} del producto #{$index} son requeridos");
                    }
                }
            }
        }
    }
}
