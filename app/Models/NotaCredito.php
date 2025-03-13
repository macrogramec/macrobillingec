<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Modelo para la gestión de Notas de Crédito Electrónicas
 *
 * Este modelo maneja las notas de crédito tanto generadas desde facturas del sistema
 * como las ingresadas manualmente. Implementa las especificaciones del SRI para
 * documentos electrónicos versión 1.0.0 hasta 2.1.0
 *
 * @property int $id Identificador único de la nota de crédito
 * @property int|null $empresa_id ID de la empresa emisora (si aplica)
 * @property int|null $establecimiento_id ID del establecimiento emisor (si aplica)
 * @property int|null $punto_emision_id ID del punto de emisión (si aplica)
 * @property int|null $factura_id ID de la factura relacionada (si aplica)
 * @property string $uuid Identificador único universal
 * @property string $estado Estado actual del documento
 * @property string $version Versión del formato XML
 * @property string $ambiente 1: Pruebas, 2: Producción
 * @property string $tipoEmision 1: Normal, 2: Contingencia
 * @property string $razonSocial Razón social del emisor
 * @property string $nombreComercial Nombre comercial del emisor
 * @property string $ruc RUC del emisor
 * @property string $claveAcceso Clave de acceso única del SRI
 * @property string $codDoc Código del documento (04: Nota de Crédito)
 * @property string $estab Código del establecimiento
 * @property string $ptoEmi Punto de emisión
 * @property string $secuencial Secuencial del documento
 * @property string $dirMatriz Dirección de la matriz
 * @property Carbon $fechaEmision Fecha de emisión del documento
 * @property string $tipoIdentificacionComprador Tipo de identificación del comprador
 * @property string $razonSocialComprador Razón social del comprador
 * @property string $identificacionComprador Número de identificación del comprador
 * @property string $rise Número RISE del comprador
 * @property string $codDocModificado Código del documento modificado
 * @property string $numDocModificado Número del documento modificado
 * @property Carbon $fechaEmisionDocSustento Fecha de emisión del documento sustento
 * @property float $totalSinImpuestos Total sin impuestos
 * @property float $valorModificacion Valor total de la modificación
 * @property string $motivo Motivo de la nota de crédito
 * @property float $totalDescuento Total de descuentos
 * @property float $totalImpuestos Total de impuestos
 * @property float $valorTotal Valor total del documento
 * @property boolean $procesadoSri Indica si fue procesado por el SRI
 * @property Carbon|null $fechaAutorizacion Fecha de autorización del SRI
 * @property string|null $numeroAutorizacion Número de autorización del SRI
 * @property array|null $infoAdicional Información adicional del documento
 * @property Carbon $created_at Fecha de creación
 * @property Carbon $updated_at Fecha de actualización
 * @property Carbon|null $deleted_at Fecha de eliminación lógica
 *
 * @method static \Illuminate\Database\Eloquent\Builder|NotaCredito porEstado(string $estado)
 * @method static \Illuminate\Database\Eloquent\Builder|NotaCredito porFechas(string $desde, string $hasta)
 * @method static \Illuminate\Database\Eloquent\Builder|NotaCredito autorizadas()
 * @method static \Illuminate\Database\Eloquent\Builder|NotaCredito pendientes()
 */
class NotaCredito extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * @var string
     */
    protected $table = 'notas_credito';

    /**
     * Estados posibles del documento
     */
    const ESTADO = [
        'CREADA' => 'CREADA',
        'FIRMADA' => 'FIRMADA',
        'ENVIADA' => 'ENVIADA',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    /**
     * Tipos de identificación según el SRI
     */
    const TIPO_IDENTIFICACION = [
        '04' => 'RUC',
        '05' => 'CEDULA',
        '06' => 'PASAPORTE',
        '07' => 'CONSUMIDOR FINAL',
        '08' => 'IDENTIFICACION EXTERIOR'
    ];

    /**
     * Tipo de documento
     */
    const TIPO_DOCUMENTO = '04'; // Nota de Crédito

    /**
     * Campos que se pueden llenar masivamente
     * @var array
     */
    protected $fillable = [
        'empresa_id',
        'establecimiento_id',
        'punto_emision_id',
        'factura_id',
        'uuid',
        'estado',
        'version',
        'ambiente',
        'tipoEmision',
        'razonSocial',
        'nombreComercial',
        'ruc',
        'claveAcceso',
        'codDoc',
        'estab',
        'ptoEmi',
        'secuencial',
        'dirMatriz',
        'fechaEmision',
        'dirEstablecimiento',
        'contribuyenteEspecial',
        'obligadoContabilidad',
        'tipoIdentificacionComprador',
        'razonSocialComprador',
        'identificacionComprador',
        'rise',
        'codDocModificado',
        'numDocModificado',
        'fechaEmisionDocSustento',
        'totalSinImpuestos',
        'valorModificacion',
        'motivo',
        'totalDescuento',
        'totalImpuestos',
        'totalConImpuestos',
        'valorTotal',
        'procesadoSri',
        'fechaAutorizacion',
        'numeroAutorizacion',
        'infoAdicional'
    ];

    /**
     * Campos que deben ser convertidos a tipos nativos
     * @var array
     */
    protected $casts = [
        'fechaEmision' => 'string',
        'fechaEmisionDocSustento' => 'string',
        'fechaAutorizacion' => 'datetime',
        'totalSinImpuestos' => 'float',
        'valorModificacion' => 'float',
        'totalDescuento' => 'float',
        'totalImpuestos' => 'float',
        'totalConImpuestos' => 'float',
        'valorTotal' => 'float',
        'infoAdicional' => 'array',
        'procesadoSri' => 'boolean',
        'historial_cambios' => 'array'
    ];

    /**
     * Relación con la empresa emisora
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación con el establecimiento emisor
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * Relación con el punto de emisión
     */
    public function puntoEmision(): BelongsTo
    {
        return $this->belongsTo(PuntoEmision::class);
    }

    /**
     * Relación con la factura original (si existe)
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Relación con los detalles de la nota de crédito
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(NotaCreditoDetalle::class);
    }

    /**
     * Relación con los estados de la nota de crédito
     */
    public function estados(): HasMany
    {
        return $this->hasMany(NotaCreditoEstado::class);
    }

    /**
     * Relación con los detalles adicionales
     */
    public function detallesAdicionales(): HasMany
    {
        return $this->hasMany(NotaCreditoDetalleAdicional::class);
    }

    /**
     * Relación con los impuestos
     */
    public function impuestos(): HasMany
    {
        return $this->hasMany(NotaCreditoImpuesto::class);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopePorFechas($query, string $desde, string $hasta)
    {
        return $query->whereBetween('fechaEmision', [$desde, $hasta]);
    }

    /**
     * Scope para obtener solo notas de crédito autorizadas
     */
    public function scopeAutorizadas($query)
    {
        return $query->where('estado', self::ESTADO['AUTORIZADA']);
    }

    /**
     * Scope para obtener notas de crédito pendientes de procesar
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', [
            self::ESTADO['CREADA'],
            self::ESTADO['FIRMADA'],
            self::ESTADO['ENVIADA']
        ]);
    }

    /**
     * Validar si la nota de crédito puede ser anulada
     */
    public function puedeSerAnulada(): bool
    {
        return in_array($this->estado, [
            self::ESTADO['CREADA'],
            self::ESTADO['AUTORIZADA']
        ]);
    }

    /**
     * Obtener el último estado registrado
     */
    public function ultimoEstado()
    {
        return $this->estados()->latest()->first();
    }

    /**
     * Obtener el número completo del documento
     */
    public function getNumeroCompletoAttribute(): string
    {
        return "{$this->estab}-{$this->ptoEmi}-{$this->secuencial}";
    }

    /**
     * Calcula el total de la nota de crédito
     */
    public function calcularTotal(): float
    {
        return $this->detalles->sum(function ($detalle) {
            return ($detalle->cantidad * $detalle->precioUnitario) - $detalle->descuento;
        });
    }

    /**
     * Actualiza el estado de la nota de crédito
     */
    public function actualizarEstado(string $nuevoEstado, string $observacion = null): void
    {
        if (!array_key_exists($nuevoEstado, self::ESTADO)) {
            throw new \InvalidArgumentException("Estado no válido: {$nuevoEstado}");
        }

        $this->estado = $nuevoEstado;
        $this->save();

        // Registrar el cambio de estado
        $this->estados()->create([
            'estado_actual' => $nuevoEstado,
            'observacion' => $observacion,
            'usuario_proceso' => auth()->user()->name ?? 'Sistema',
            'fecha_proceso' => now()
        ]);
    }

    /**
     * Verifica si la nota de crédito está autorizada
     */
    public function estaAutorizada(): bool
    {
        return $this->estado === self::ESTADO['AUTORIZADA'];
    }

    /**
     * Obtiene el XML de la nota de crédito
     */
    public function generarXML(): string
    {
        // Lógica para generar el XML según especificaciones del SRI
        // Este método debe ser implementado según los requerimientos
        return '';
    }

    /**
     * Obtiene el PDF de la nota de crédito
     */
    public function generarPDF()
    {
        // Lógica para generar el PDF
        // Este método debe ser implementado según los requerimientos
        return null;
    }

    /**
     * Valida la estructura de la nota de crédito
     */
    public function validarEstructura(): array
    {
        $errores = [];

        // Validaciones básicas
        if (empty($this->claveAcceso)) {
            $errores[] = 'La clave de acceso es requerida';
        }

        if (empty($this->detalles)) {
            $errores[] = 'La nota de crédito debe tener al menos un detalle';
        }

        // Más validaciones según requerimientos

        return $errores;
    }
}
