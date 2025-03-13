<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para gestionar los estados y el ciclo de vida de las Notas de Crédito
 *
 * Este modelo maneja el historial de estados, procesos de autorización, firmas
 * y comunicación con el SRI para las notas de crédito electrónicas.
 *
 * @property int $id Identificador único del estado
 * @property int $nota_credito_id ID de la nota de crédito relacionada
 * @property string $estado_actual Estado actual del documento
 * @property string|null $estado_sri Estado asignado por el SRI
 * @property \DateTime|null $fecha_firma Fecha y hora de la firma electrónica
 * @property bool|null $firmado_exitoso Indica si la firma fue exitosa
 * @property string|null $error_firma Error en el proceso de firma
 * @property string|null $certificado_firma Certificado usado para firmar
 * @property \DateTime|null $fecha_autorizacion Fecha de autorización del SRI
 * @property string|null $numero_autorizacion Número de autorización del SRI
 * @property array|null $errores Lista de errores registrados
 * @property int $numero_intentos Número de intentos de proceso
 */
class NotaCreditoEstado extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * @var string
     */
    protected $table = 'nota_credito_estados';

    /**
     * Estados posibles del SRI
     */
    const ESTADO_SRI = [
        'RECIBIDA' => 'RECIBIDA',
        'EN_PROCESO' => 'EN PROCESO',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    /**
     * Campos que se pueden llenar masivamente
     * @var array
     */
    protected $fillable = [
        'nota_credito_id',
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
        'numero_intentos',
        'ultimo_intento',
        'proximo_intento',
        'requiere_reenvio',
        'motivo_reenvio',
        'ip_origen',
        'usuario_proceso',
        'historial_cambios'
    ];

    /**
     * Campos que deben ser convertidos a tipos nativos
     * @var array
     */
    protected $casts = [
        'fecha_firma' => 'datetime',
        'fecha_envio_sri' => 'datetime',
        'fecha_recepcion_sri' => 'datetime',
        'fecha_autorizacion' => 'datetime',
        'ultimo_intento' => 'datetime',
        'proximo_intento' => 'datetime',
        'firmado_exitoso' => 'boolean',
        'envio_exitoso' => 'boolean',
        'requiere_reenvio' => 'boolean',
        'errores' => 'array',
        'advertencias' => 'array',
        'respuesta_recepcion_sri' => 'array',
        'respuesta_autorizacion_sri' => 'array',
        'historial_cambios' => 'array'
    ];

    /**
     * Relación con la nota de crédito
     */
    public function notaCredito(): BelongsTo
    {
        return $this->belongsTo(NotaCredito::class);
    }

    /**
     * Registra un error en el proceso
     */
    public function registrarError(string $codigo, string $mensaje, ?string $informacionAdicional = null, string $tipo = 'ERROR'): void
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

    /**
     * Registra un cambio de estado
     */
    public function registrarCambioEstado(string $estadoAnterior, string $estadoNuevo, ?string $observacion = null): void
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

    /**
     * Verifica si necesita reintento
     */
    public function necesitaReintento(): bool
    {
        return $this->numero_intentos < 3 &&
            !in_array($this->estado_actual, ['AUTORIZADA', 'ANULADA']) &&
            $this->requiere_reenvio;
    }

    /**
     * Obtiene el último error registrado
     */
    public function ultimoError(): ?array
    {
        $errores = $this->errores ?? [];
        return end($errores) ?: null;
    }

    /**
     * Verifica si tiene errores críticos
     */
    public function tieneErroresCriticos(): bool
    {
        $errores = $this->errores ?? [];
        return collect($errores)->contains('tipo', 'ERROR');
    }
}
