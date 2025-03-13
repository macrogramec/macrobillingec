<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CodigoRetencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'codigos_retencion';

    protected $fillable = [
        'tipo_impuesto',
        'codigo',
        'concepto',
        'porcentaje',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'tipo_persona',
        'tipo_regimen',
        'categoria',
        'validaciones'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
        'validaciones' => 'array'
    ];

    // Relación con las retenciones
    public function retencionDetalles()
    {
        return $this->hasMany(RetencionDetalle::class);
    }

    // Relación con el historial de cambios
    public function historial()
    {
        return $this->hasMany(HistorialCodigoRetencion::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where(function($q) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now());
            });
    }

    public function scopePorTipoImpuesto($query, $tipo)
    {
        return $query->where('tipo_impuesto', $tipo);
    }

    // Métodos
    public function estaVigente(): bool
    {
        $now = now();
        return $this->activo &&
            $now->gte($this->fecha_inicio) &&
            ($this->fecha_fin === null || $now->lte($this->fecha_fin));
    }

    public function registrarCambio(float $nuevoPorcentaje, string $motivo, string $usuario): void
    {
        $this->historial()->create([
            'porcentaje_anterior' => $this->porcentaje,
            'porcentaje_nuevo' => $nuevoPorcentaje,
            'motivo' => $motivo,
            'usuario' => $usuario
        ]);

        $this->update(['porcentaje' => $nuevoPorcentaje]);
    }

    public function desactivar(string $motivo, string $usuario): void
    {
        $this->update([
            'activo' => false,
            'fecha_fin' => now()
        ]);

        $this->historial()->create([
            'porcentaje_anterior' => $this->porcentaje,
            'porcentaje_nuevo' => $this->porcentaje,
            'motivo' => "Desactivación: $motivo",
            'usuario' => $usuario
        ]);
    }

    // Constantes para tipos de impuestos
    const TIPO_IMPUESTO = [
        'IR' => 'Impuesto a la Renta',
        'IV' => 'IVA'
    ];

    // Constantes para categorías
    const CATEGORIA = [
        'normal' => 'Normal',
        'dividendos' => 'Dividendos',
        'participaciones' => 'Participaciones'
    ];

    // Constantes para tipos de persona
    const TIPO_PERSONA = [
        'natural' => 'Persona Natural',
        'sociedad' => 'Sociedad'
    ];

    // Constantes para tipos de régimen
    const TIPO_REGIMEN = [
        'rimpe' => 'RIMPE',
        'general' => 'General',
        'especial' => 'Especial'
    ];
}
