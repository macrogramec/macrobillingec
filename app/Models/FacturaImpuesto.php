<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaImpuesto extends Model
{
    protected $fillable = [
        'factura_id',
        'tipo_impuesto_codigo',
        'tarifa_codigo',
        'base_imponible',
        'porcentaje',
        'valor_especifico',
        'valor'
    ];

    protected $casts = [
        'base_imponible' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'valor_especifico' => 'decimal:6',
        'valor' => 'decimal:2'
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function tipoImpuesto(): BelongsTo
    {
        return $this->belongsTo(TipoImpuesto::class, 'tipo_impuesto_codigo', 'codigo');
    }
}
