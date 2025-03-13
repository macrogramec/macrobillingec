<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiquidacionCompraDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'liquidacion_compra_id',
        'linea',
        'codigo_principal',
        'codigo_auxiliar',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento',
        'precio_total_sin_impuesto',
        'detalles_adicionales',
        'unidad_medida',
        'precio_sin_subsidio',
        'codigo_partida_arancelaria',
        'precio_referencial_unitario',
        'pais_origen',
        'pais_adquisicion'
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'precio_unitario' => 'decimal:6',
        'descuento' => 'decimal:2',
        'precio_total_sin_impuesto' => 'decimal:2',
        'precio_sin_subsidio' => 'decimal:6',
        'precio_referencial_unitario' => 'decimal:6',
        'detalles_adicionales' => 'array'
    ];

    public function liquidacionCompra(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompra::class);
    }

    public function impuestos(): HasMany
    {
        return $this->hasMany(LiquidacionCompraImpuesto::class);
    }

    // Métodos de cálculo
    public function calcularSubtotal(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    public function calcularTotalSinImpuesto(): float
    {
        return $this->calcularSubtotal() - ($this->descuento ?? 0);
    }
}
