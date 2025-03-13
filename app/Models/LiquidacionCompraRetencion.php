<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiquidacionCompraRetencion extends Model
{
    use HasFactory;
    protected $table = 'liquidacion_compra_retenciones';

    protected $fillable = [
        'liquidacion_compra_id',
        'codigo',
        'codigo_porcentaje',
        'tarifa',
        'base_imponible',
        'valor_retenido',
        'tipo_impuesto',
        'porcentaje',
        'tipo_renta',
        'codigo_doctributario'
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'valor_retenido' => 'decimal:2',
        'porcentaje' => 'decimal:2'
    ];

    public function liquidacionCompra(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompra::class);
    }

    // Métodos de cálculo
    public function calcularValorRetenido(): float
    {
        return round(($this->base_imponible * $this->porcentaje) / 100, 2);
    }

    public function validarCalculo(): bool
    {
        return abs($this->calcularValorRetenido() - $this->valor_retenido) < 0.01;
    }
}
