<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Carbon\Carbon;

class LiquidacionCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'liquidaciones_compra';

    protected $fillable = [
        'uuid',
        'estado',
        'version',
        'empresa_id',
        'establecimiento_id',
        'punto_emision_id',
        'ambiente',
        'tipo_emision',
        'razon_social',
        'nombre_comercial',
        'ruc',
        'clave_acceso',
        'cod_doc',
        'estab',
        'pto_emi',
        'secuencial',
        'dir_matriz',
        'fecha_emision',
        'periodo_fiscal',
        'dir_establecimiento',
        'contribuyente_especial',
        'obligado_contabilidad',
        'tipo_identificacion_proveedor',
        'identificacion_proveedor',
        'razon_social_proveedor',
        'direccion_proveedor',
        'email_proveedor',
        'telefono_proveedor',
        'tipo_proveedor',
        'total_sin_impuestos',
        'total_descuento',
        'total_ice',
        'total_iva',
        'total_irbpnr',
        'total_sin_impuestos_sin_ice',
        'total_impuestos',
        'importe_total',
        'moneda',
        'procesado_sri',
        'fecha_autorizacion',
        'numero_autorizacion',
        'ambiente_autorizacion',
        'info_adicional',
        'version_actual',
        'historial_cambios'
    ];

    protected $casts = [
        'procesado_sri' => 'boolean',
        'fecha_autorizacion' => 'datetime',
        'info_adicional' => 'array',
        'historial_cambios' => 'array',
    ];

    // Relaciones
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function puntoEmision(): BelongsTo
    {
        return $this->belongsTo(PuntoEmision::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(LiquidacionCompraDetalle::class);
    }
    public function pagos(): HasMany
    {
        return $this->hasMany(LiquidacionCompraPago::class, 'liquidacion_id');
    }

    public function estados(): HasMany
    {
        return $this->hasMany(LiquidacionCompraEstado::class);
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(LiquidacionCompraRetencion::class);
    }

    public function detallesAdicionales(): HasMany
    {
        return $this->hasMany(LiquidacionCompraDetalleAdicional::class);
    }

    public function ultimoEstado(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompraEstado::class)
            ->latest();
    }

    // Scopes
    public function scopeAutorizadas($query)
    {
        return $query->where('procesado_sri', true)
            ->whereNotNull('numero_autorizacion');
    }

    public function scopePendientes($query)
    {
        return $query->where('procesado_sri', false);
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    // Métodos
    public function estaAutorizada(): bool
    {
        return $this->procesado_sri && !is_null($this->numero_autorizacion);
    }

    public function puedeSerModificada(): bool
    {
        return !$this->estaAutorizada() && $this->estado !== 'ANULADA';
    }
}
