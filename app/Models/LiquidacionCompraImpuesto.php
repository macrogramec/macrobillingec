<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LiquidacionCompraImpuesto extends Model
{
    use HasFactory;

    protected $fillable = [
        'liquidacion_compra_detalle_id',
        'codigo',
        'codigo_porcentaje',
        'tarifa',
        'base_imponible',
        'valor',
        'version',
        'activo',
        'usuario_creacion',
        'usuario_modificacion'
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'valor' => 'decimal:2',
        'activo' => 'boolean'
    ];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompraDetalle::class, 'liquidacion_compra_detalle_id');
    }

    // Métodos de cálculo
    public function calcularValor(): float
    {
        return round(($this->base_imponible * $this->tarifa) / 100, 2);
    }

    public function validarCalculo(): bool
    {
        return abs($this->calcularValor() - $this->valor) < 0.01;
    }
}
