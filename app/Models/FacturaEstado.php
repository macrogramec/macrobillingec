<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaEstado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'factura_estados';

    protected $fillable = [
        'factura_id',
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
        'proceso_contingencia',
        'fecha_inicio_contingencia',
        'fecha_fin_contingencia',
        'motivo_contingencia',
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
        'fecha_inicio_contingencia' => 'datetime',
        'fecha_fin_contingencia' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'ultimo_intento' => 'datetime',
        'proximo_intento' => 'datetime',
        'fecha_notificacion' => 'datetime',
        'firmado_exitoso' => 'boolean',
        'envio_exitoso' => 'boolean',
        'proceso_contingencia' => 'boolean',
        'anulado' => 'boolean',
        'requiere_reenvio' => 'boolean',
        'notificacion_enviada' => 'boolean',
        'respuesta_recepcion_sri' => 'array',
        'respuesta_autorizacion_sri' => 'array',
        'respuesta_anulacion_sri' => 'array',
        'errores' => 'array',
        'advertencias' => 'array',
        'historial_cambios' => 'array'
    ];

    const ESTADO = [
        'CREADA' => 'CREADA',
        'FIRMADA' => 'FIRMADA',
        'ENVIADA' => 'ENVIADA',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    const ESTADO_SRI = [
        'RECIBIDA' => 'RECIBIDA',
        'EN_PROCESO' => 'EN PROCESO',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    const JOB_STATUS = [
        'PENDIENTE' => 'PENDIENTE',
        'EN_PROCESO' => 'EN PROCESO',
        'COMPLETADO' => 'COMPLETADO',
        'FALLIDO' => 'FALLIDO',
        'REINTENTANDO' => 'REINTENTANDO'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function registrarError($codigo, $mensaje, $informacionAdicional = null, $tipo = 'ERROR')
    {
        $errores = $this->errores ?? [];
        $errores[] = [
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'informacionAdicional' => $informacionAdicional,
            'tipo' => $tipo,
            'fecha' => now()->toIso8601String()
        ];
        $this->errores = $errores;
        $this->save();
    }

    public function registrarCambioEstado($estadoAnterior, $estadoNuevo, $observacion = null)
    {
        $historial = $this->historial_cambios ?? [];
        $historial[] = [
            'fecha' => now()->toIso8601String(),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'observacion' => $observacion,
            'usuario' => $this->usuario_proceso,
            'ip' => $this->ip_origen
        ];
        $this->historial_cambios = $historial;
        $this->save();
    }

    public function necesitaReintento()
    {
        return $this->numero_intentos < 3 &&
            !in_array($this->estado_actual, ['AUTORIZADA', 'ANULADA']) &&
            $this->requiere_reenvio;
    }
}
