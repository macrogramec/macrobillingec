<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaDetalleImpuesto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'factura_detalle_id',
        'codigo',
        'codigo_porcentaje',
        'tarifa',
        'base_imponible',
        'valor',
        'version'
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'valor' => 'decimal:2'
    ];

    public function detalle()
    {
        return $this->belongsTo(FacturaDetalle::class, 'factura_detalle_id');
    }
}
