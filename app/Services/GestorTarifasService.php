<?php

namespace App\Services;

use App\Models\{TipoImpuesto, TarifaImpuesto, HistorialTarifa};
use App\Exceptions\{TarifaInvalidaException, TarifaExistenteException};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GestorTarifasService
{
    /**
     * Crear una nueva tarifa de impuesto
     */
    public function crearTarifa(array $datos): TarifaImpuesto
    {
        $this->validarNuevaTarifa($datos);

        return DB::transaction(function () use ($datos) {
            // Desactivar tarifa anterior si existe
            $this->desactivarTarifaAnterior(
                $datos['tipo_impuesto_codigo'],
                $datos['codigo'],
                $datos['fecha_inicio']
            );

            return TarifaImpuesto::create([
                'tipo_impuesto_codigo' => $datos['tipo_impuesto_codigo'],
                'codigo' => $datos['codigo'],
                'codigo_sri' => $datos['codigo_sri'],
                'descripcion' => $datos['descripcion'],
                'porcentaje' => $datos['porcentaje'] ?? 0,
                'tipo_calculo' => $datos['tipo_calculo'],
                'valor_especifico' => $datos['valor_especifico'] ?? null,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'] ?? null,
                'activo' => true
            ]);
        });
    }

    /**
     * Actualizar una tarifa existente
     */
    public function actualizarTarifa(TarifaImpuesto $tarifa, array $datos, string $motivo, string $usuario): TarifaImpuesto
    {
        return DB::transaction(function () use ($tarifa, $datos, $motivo, $usuario) {
            // Registrar cambios en el historial
            HistorialTarifa::create([
                'tarifa_impuesto_id' => $tarifa->id,
                'porcentaje_anterior' => $tarifa->porcentaje,
                'porcentaje_nuevo' => $datos['porcentaje'] ?? $tarifa->porcentaje,
                'valor_especifico_anterior' => $tarifa->valor_especifico,
                'valor_especifico_nuevo' => $datos['valor_especifico'] ?? $tarifa->valor_especifico,
                'motivo' => $motivo,
                'documento_respaldo' => $datos['documento_respaldo'] ?? null,
                'usuario' => $usuario
            ]);

            // Actualizar la tarifa
            $tarifa->update([
                'descripcion' => $datos['descripcion'] ?? $tarifa->descripcion,
                'porcentaje' => $datos['porcentaje'] ?? $tarifa->porcentaje,
                'valor_especifico' => $datos['valor_especifico'] ?? $tarifa->valor_especifico,
                'fecha_fin' => $datos['fecha_fin'] ?? $tarifa->fecha_fin,
                'activo' => $datos['activo'] ?? $tarifa->activo
            ]);

            return $tarifa->fresh();
        });
    }

    /**
     * Desactivar una tarifa
     */
    public function desactivarTarifa(TarifaImpuesto $tarifa, string $motivo, string $usuario): void
    {
        DB::transaction(function () use ($tarifa, $motivo, $usuario) {
            // Registrar en historial
            HistorialTarifa::create([
                'tarifa_impuesto_id' => $tarifa->id,
                'porcentaje_anterior' => $tarifa->porcentaje,
                'porcentaje_nuevo' => $tarifa->porcentaje,
                'valor_especifico_anterior' => $tarifa->valor_especifico,
                'valor_especifico_nuevo' => $tarifa->valor_especifico,
                'motivo' => $motivo,
                'usuario' => $usuario
            ]);

            // Desactivar tarifa
            $tarifa->update([
                'activo' => false,
                'fecha_fin' => Carbon::now()
            ]);
        });
    }

    /**
     * Obtener tarifas vigentes por tipo de impuesto
     */
    public function obtenerTarifasVigentes(string $tipoImpuestoCodigo): array
    {
        return TarifaImpuesto::where('tipo_impuesto_codigo', $tipoImpuestoCodigo)
            ->where('activo', true)
            ->where('fecha_inicio', '<=', Carbon::now())
            ->where(function ($query) {
                $query->where('fecha_fin', '>=', Carbon::now())
                    ->orWhereNull('fecha_fin');
            })
            ->get()
            ->toArray();
    }

    /**
     * Verificar si existe superposición de fechas para una tarifa
     */
    protected function existeSuperposicion(string $tipoImpuestoCodigo, string $codigo, Carbon $fechaInicio, ?Carbon $fechaFin): bool
    {
        $query = TarifaImpuesto::where('tipo_impuesto_codigo', $tipoImpuestoCodigo)
            ->where('codigo', $codigo)
            ->where('activo', true)
            ->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->where(function ($q) use ($fechaInicio) {
                    $q->where('fecha_inicio', '<=', $fechaInicio)
                        ->where(function ($q) use ($fechaInicio) {
                            $q->where('fecha_fin', '>=', $fechaInicio)
                                ->orWhereNull('fecha_fin');
                        });
                })->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                    $q->where('fecha_inicio', '>=', $fechaInicio)
                        ->where('fecha_inicio', '<=', $fechaFin ?? Carbon::maxValue());
                });
            });

        return $query->exists();
    }

    /**
     * Validar los datos de una nueva tarifa
     */
    protected function validarNuevaTarifa(array $datos): void
    {
        // Validar que existe el tipo de impuesto
        if (!TipoImpuesto::where('codigo', $datos['tipo_impuesto_codigo'])->exists()) {
            throw new TarifaInvalidaException("Tipo de impuesto no existe");
        }

        // Validar tipo de cálculo y valores necesarios
        switch ($datos['tipo_calculo']) {
            case 'PORCENTAJE':
                if (!isset($datos['porcentaje']) || $datos['porcentaje'] < 0) {
                    throw new TarifaInvalidaException("Porcentaje inválido para tipo de cálculo PORCENTAJE");
                }
                break;
            case 'ESPECIFICO':
                if (!isset($datos['valor_especifico']) || $datos['valor_especifico'] < 0) {
                    throw new TarifaInvalidaException("Valor específico inválido para tipo de cálculo ESPECIFICO");
                }
                break;
            case 'MIXTO':
                if (!isset($datos['porcentaje']) || $datos['porcentaje'] < 0 ||
                    !isset($datos['valor_especifico']) || $datos['valor_especifico'] < 0) {
                    throw new TarifaInvalidaException("Valores inválidos para tipo de cálculo MIXTO");
                }
                break;
            default:
                throw new TarifaInvalidaException("Tipo de cálculo no válido");
        }

        // Validar fechas
        $fechaInicio = Carbon::parse($datos['fecha_inicio']);
        $fechaFin = isset($datos['fecha_fin']) ? Carbon::parse($datos['fecha_fin']) : null;

        if ($fechaFin && $fechaInicio->gt($fechaFin)) {
            throw new TarifaInvalidaException("Fecha de inicio no puede ser posterior a fecha fin");
        }

        // Validar superposición de fechas
        if ($this->existeSuperposicion(
            $datos['tipo_impuesto_codigo'],
            $datos['codigo'],
            $fechaInicio,
            $fechaFin
        )) {
            throw new TarifaExistenteException("Ya existe una tarifa vigente para el período especificado");
        }
    }

    /**
     * Desactivar tarifa anterior si existe
     */
    protected function desactivarTarifaAnterior(string $tipoImpuestoCodigo, string $codigo, string $fechaInicio): void
    {
        TarifaImpuesto::where('tipo_impuesto_codigo', $tipoImpuestoCodigo)
            ->where('codigo', $codigo)
            ->where('activo', true)
            ->where('fecha_fin', '>=', $fechaInicio)
            ->update([
                'fecha_fin' => Carbon::parse($fechaInicio)->subDay(),
                'activo' => false
            ]);
    }
}
