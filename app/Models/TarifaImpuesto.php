<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarifaImpuesto extends Model
{
    //
    protected $table = 'tarifas_impuestos';

    protected $fillable = [
        'tipo_impuesto_codigo',
        'codigo',
        'codigo_sri',
        'descripcion',
        'porcentaje',
        'tipo_calculo',
        'valor_especifico',
        'fecha_inicio',
        'fecha_fin',
        'activo'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'valor_especifico' => 'decimal:6',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'activo' => 'boolean'
    ];

    public function tipoImpuesto(): BelongsTo
    {
        return $this->belongsTo(TipoImpuesto::class, 'tipo_impuesto_codigo', 'codigo');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialTarifa::class);
    }

    public function condiciones(): HasMany
    {
        return $this->hasMany(CondicionImpuesto::class);
    }

    public function estaVigente(): bool
    {
        $ahora = now();
        return $this->activo &&
            $this->fecha_inicio <= $ahora &&
            ($this->fecha_fin === null || $this->fecha_fin >= $ahora);
    }

    public function calcularValor(float $baseImponible): float
    {
        switch ($this->tipo_calculo) {
            case 'PORCENTAJE':
                return round(($baseImponible * $this->porcentaje) / 100, 2);
            case 'ESPECIFICO':
                return round($this->valor_especifico, 2);
            case 'MIXTO':
                $valorPorcentaje = ($baseImponible * $this->porcentaje) / 100;
                return round($valorPorcentaje + $this->valor_especifico, 2);
            default:
                throw new \Exception("Tipo de cálculo no soportado: {$this->tipo_calculo}");
        }
    }

    public static function getPorCodigosSRI(string $tipoCodigoSRI, string $tarifaCodigoSRI)
    {
        return static::where('codigo_sri', $tarifaCodigoSRI)
            ->whereHas('tipoImpuesto', function ($query) use ($tipoCodigoSRI) {
                $query->where('codigo_sri', $tipoCodigoSRI);
            })
            ->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where(function ($query) {
                $query->where('fecha_fin', '>=', now())
                    ->orWhereNull('fecha_fin');
            })
            ->first();
    }
}
