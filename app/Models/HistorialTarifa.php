<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class HistorialTarifa extends Model
{
    //
    protected $table = 'historial_tarifas';

    protected $fillable = [
        'tarifa_impuesto_id',
        'porcentaje_anterior',
        'porcentaje_nuevo',
        'valor_especifico_anterior',
        'valor_especifico_nuevo',
        'motivo',
        'documento_respaldo',
        'usuario'
    ];

    protected $casts = [
        'porcentaje_anterior' => 'decimal:2',
        'porcentaje_nuevo' => 'decimal:2',
        'valor_especifico_anterior' => 'decimal:6',
        'valor_especifico_nuevo' => 'decimal:6'
    ];

    public function tarifaImpuesto(): BelongsTo
    {
        return $this->belongsTo(TarifaImpuesto::class);
    }

}
