<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class LiquidacionCompraEstado extends Model
{
    use HasFactory;

    protected $fillable = [
        'liquidacion_compra_id',
        'estado_anterior',
        'estado_actual',
        'estado_sri',
        'fecha_firma',
        'firmado_exitoso',
        'error_firma',
        'certificado_firma',
        'fecha_envio_sri',
        'codigo_envio_sri',
        'mensaje_envio_sri',
        'envio_exitoso',
        'fecha_autorizacion',
        'respuesta_sri',
        'observaciones',
        'numero_intentos',
        'ultimo_intento',
        'proximo_intento',
        'requiere_reenvio',
        'motivo_reenvio',
        'ip_origen',
        'usuario_proceso',
        'metadata'
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
        'fecha_envio_sri' => 'datetime',
        'fecha_autorizacion' => 'datetime',
        'ultimo_intento' => 'datetime',
        'proximo_intento' => 'datetime',
        'firmado_exitoso' => 'boolean',
        'envio_exitoso' => 'boolean',
        'requiere_reenvio' => 'boolean',
        'respuesta_sri' => 'array',
        'metadata' => 'array'
    ];

    public function liquidacionCompra(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompra::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Métodos
    public function necesitaReintento(): bool
    {
        return $this->numero_intentos < 3 &&
            !in_array($this->estado_actual, ['AUTORIZADA', 'ANULADA']) &&
            $this->requiere_reenvio;
    }
}
