<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CondicionImpuesto extends Model
{
    //
    protected $table = 'condiciones_impuestos';

    protected $fillable = [
        'tarifa_impuesto_id',
        'tipo_condicion',
        'parametros',
        'validaciones',
        'activo'
    ];

    protected $casts = [
        'parametros' => 'array',
        'validaciones' => 'array',
        'activo' => 'boolean'
    ];

    public function tarifaImpuesto(): BelongsTo
    {
        return $this->belongsTo(TarifaImpuesto::class);
    }

    public function validarCondicion($valor): bool
    {
        // Implementar lógica de validación según tipo_condicion y parámetros
        return true;
    }
}
