<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetencionEstado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retencion_estados';

    protected $fillable = [
        'retencion_id',
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
        'fecha_recepcion_sri',
        'estado_recepcion_sri',
        'respuesta_recepcion_sri',
        'observaciones_recepcion',
        'fecha_autorizacion',
        'numero_autorizacion',
        'ambiente_autorizacion',
        'respuesta_autorizacion_sri',
        'observaciones_autorizacion',
        'errores',
        'advertencias',
        'anulado',
        'fecha_anulacion',
        'motivo_anulacion',
        'respuesta_anulacion_sri',
        'numero_intentos',
        'ultimo_intento',
        'proximo_intento',
        'requiere_reenvio',
        'motivo_reenvio',
        'ip_origen',
        'usuario_proceso',
        'historial_cambios',
        'notificacion_enviada',
        'fecha_notificacion',
        'email_notificacion',
        'error_notificacion',
        'job_id',
        'job_status',
        'job_error'
    ];

    protected $casts = [
        'fecha_firma' => 'datetime',
        'fecha_envio_sri' => 'datetime',
        'fecha_recepcion_sri' => 'datetime',
        'fecha_autorizacion' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'ultimo_intento' => 'datetime',
        'proximo_intento' => 'datetime',
        'fecha_notificacion' => 'datetime',
        'firmado_exitoso' => 'boolean',
        'envio_exitoso' => 'boolean',
        'anulado' => 'boolean',
        'requiere_reenvio' => 'boolean',
        'notificacion_enviada' => 'boolean',
        'errores' => 'array',
        'advertencias' => 'array',
        'respuesta_recepcion_sri' => 'array',
        'respuesta_autorizacion_sri' => 'array',
        'respuesta_anulacion_sri' => 'array',
        'historial_cambios' => 'array'
    ];

    public function retencion(): BelongsTo
    {
        return $this->belongsTo(Retencion::class);
    }

    public function requireReintento(): bool
    {
        return !$this->envio_exitoso &&
            $this->numero_intentos < 3 &&
            !in_array($this->estado_actual, ['AUTORIZADA', 'ANULADA']);
    }
}
