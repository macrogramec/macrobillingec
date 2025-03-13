<?php

namespace App\Services\EndPoints\Empresa;

use App\Models\PuntoEmision;
use App\Models\HistorialSecuencial;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class PuntoEmisionService
{
    public function getAll(int $establecimientoId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = PuntoEmision::query()
            ->where('establecimiento_id', $establecimientoId);

        // Aplicar filtros
        if (isset($filters['codigo'])) {
            $query->where('codigo', $filters['codigo']);
        }
        if (isset($filters['tipo_comprobante'])) {
            $query->where('tipo_comprobante', $filters['tipo_comprobante']);
        }
        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?PuntoEmision
    {
        return PuntoEmision::with('historialSecuenciales')->find($id);
    }

    /*
    public function create(array $data): PuntoEmision
    {
        return DB::transaction(function () use ($data) {
            return PuntoEmision::create($data);
        });
    }
    */
    public function create(array $data): PuntoEmision
    {
        try {
            return DB::transaction(function () use ($data) {
                // Verificar si ya existe
                $existente = PuntoEmision::where([
                    'establecimiento_id' => $data['establecimiento_id'],
                    'codigo' => $data['codigo'],
                    'tipo_comprobante' => $data['tipo_comprobante']
                ])->first();

                if ($existente) {
                    throw new \Exception(
                        'Ya existe un punto de emisión con este código y tipo de comprobante para este establecimiento',
                        23000
                    );
                }

                return PuntoEmision::create($data);
            });
        } catch (\Exception $e) {
            // Capturar error específico de duplicación
            if ($e->getCode() == 23000) {
                throw new \Exception(
                    'Ya existe este punto de emisión para el establecimiento',
                    409
                );
            }
            throw $e;
        }
    }

    public function update(PuntoEmision $puntoEmision, array $data): PuntoEmision
    {
        return DB::transaction(function () use ($puntoEmision, $data) {
            $puntoEmision->update($data);
            return $puntoEmision->fresh();
        });
    }

    public function delete(PuntoEmision $puntoEmision): bool
    {
        return DB::transaction(function () use ($puntoEmision) {
            // Aquí podrías agregar validaciones adicionales si son necesarias
            return $puntoEmision->delete();
        });
    }

    public function updateSecuencial(PuntoEmision $puntoEmision, int $nuevoSecuencial, string $motivo): PuntoEmision
    {
        if ($nuevoSecuencial <= 0) {
            throw new \InvalidArgumentException("El secuencial debe ser un número positivo");
        }

        if ($nuevoSecuencial < $puntoEmision->secuencial_actual) {
            throw new \InvalidArgumentException(
                "El nuevo secuencial ({$nuevoSecuencial}) no puede ser menor al actual ({$puntoEmision->secuencial_actual})"
            );
        }

        DB::transaction(function () use ($puntoEmision, $nuevoSecuencial, $motivo) {
            // Guardar historial
            HistorialSecuencial::create([
                'punto_emision_id' => $puntoEmision->id,
                'tipo_comprobante' => $puntoEmision->tipo_comprobante,
                'secuencial_anterior' => $puntoEmision->secuencial_actual,
                'secuencial_nuevo' => $nuevoSecuencial,
                'motivo' => $motivo,
                'created_by' => auth()->id()
            ]);

            // Actualizar secuencial
            $puntoEmision->update([
                'secuencial_actual' => $nuevoSecuencial
            ]);
        });

        return $puntoEmision->fresh(); // Retornamos el modelo actualizado
    }
}
