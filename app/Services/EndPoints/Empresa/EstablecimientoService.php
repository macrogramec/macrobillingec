<?php

namespace App\Services\EndPoints\Empresa;

use App\Models\Establecimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class EstablecimientoService
{
    public function getAll(int $empresaId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Establecimiento::query()
            ->where('empresa_id', $empresaId);

        // Aplicar filtros
        if (isset($filters['codigo'])) {
            $query->where('codigo', $filters['codigo']);
        }
        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Establecimiento
    {
        return Establecimiento::with('puntosEmision')->find($id);
    }

    public function create(array $data): Establecimiento
    {
        return DB::transaction(function () use ($data) {
            return Establecimiento::create($data);
        });
    }

    public function update(Establecimiento $establecimiento, array $data): Establecimiento
    {
        return DB::transaction(function () use ($establecimiento, $data) {
            $establecimiento->update($data);
            return $establecimiento->fresh();
        });
    }

    public function delete(Establecimiento $establecimiento): bool
    {
        return DB::transaction(function () use ($establecimiento) {
            // Verificar si tiene puntos de emisión
            if ($establecimiento->puntosEmision()->count() > 0) {
                throw new \Exception('No se puede eliminar el establecimiento porque tiene puntos de emisión asociados.');
            }
            return $establecimiento->delete();
        });
    }
}
