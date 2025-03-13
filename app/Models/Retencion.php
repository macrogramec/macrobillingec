<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @OA\Schema(
 *     schema="Retencion",
 *     title="Retención",
 *     description="Modelo de retención electrónica",
 *     required={"empresa_id", "establecimiento_id", "punto_emision_id"},
 *     @OA\Property(property="id", type="integer", example=1, description="ID único de la retención"),
 *     @OA\Property(property="empresa_id", type="integer", example=1),
 *     @OA\Property(property="establecimiento_id", type="integer", example=1),
 *     @OA\Property(property="punto_emision_id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="estado", type="string", enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}),
 *     @OA\Property(property="version", type="string", example="1.0.0"),
 *     @OA\Property(property="ambiente", type="string", enum={"1", "2"}, description="1: Pruebas, 2: Producción"),
 *     @OA\Property(property="tipo_emision", type="string", example="1", description="1: Normal"),
 *     @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
 *     @OA\Property(property="nombre_comercial", type="string", nullable=true),
 *     @OA\Property(property="ruc", type="string", example="0992877878001"),
 *     @OA\Property(property="clave_acceso", type="string", example="2311202301099287787800110010010000000011234567813"),
 *     @OA\Property(property="cod_doc", type="string", example="07", description="07: Comprobante de Retención"),
 *     @OA\Property(property="estab", type="string", example="001"),
 *     @OA\Property(property="pto_emi", type="string", example="001"),
 *     @OA\Property(property="secuencial", type="string", example="000000001"),
 *     @OA\Property(property="dir_matriz", type="string"),
 *     @OA\Property(property="fecha_emision", type="string", format="date"),
 *     @OA\Property(property="periodo_fiscal", type="string", example="04/2024"),
 *     @OA\Property(property="tipo_identificacion_sujeto", type="string"),
 *     @OA\Property(property="razon_social_sujeto", type="string"),
 *     @OA\Property(property="identificacion_sujeto", type="string"),
 *     @OA\Property(property="tipo_sujeto", type="string", enum={"natural", "sociedad"}),
 *     @OA\Property(property="regimen_sujeto", type="string", enum={"rimpe", "general"}),
 *     @OA\Property(property="ejercicio_fiscal", type="string"),
 *     @OA\Property(property="fecha_pago", type="string", format="date"),
 *     @OA\Property(property="valor_pago", type="number", format="decimal"),
 *     @OA\Property(property="beneficiario_tipo", type="string"),
 *     @OA\Property(property="beneficiario_id", type="string"),
 *     @OA\Property(property="beneficiario_razon_social", type="string"),
 *     @OA\Property(property="ingreso_gravado", type="number", format="decimal"),
 *     @OA\Property(property="total_retenido", type="number", format="decimal"),
 *     @OA\Property(property="fecha_autorizacion", type="string", format="date-time"),
 *     @OA\Property(property="numero_autorizacion", type="string"),
 *     @OA\Property(property="info_adicional", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class Retencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retenciones';

    const ESTADO = [
        'CREADA' => 'CREADA',
        'FIRMADA' => 'FIRMADA',
        'ENVIADA' => 'ENVIADA',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    protected $fillable = [
        'empresa_id',
        'establecimiento_id',
        'punto_emision_id',
        'uuid',
        'estado',
        'version',
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
        'fechaEmision',
        'periodo_fiscal',
        'tipo_identificacion_sujeto',
        'razon_social_sujeto',
        'identificacion_sujeto',
        'tipo_sujeto',
        'regimen_sujeto',
        'email',
        'ejercicio_fiscal',
        'fecha_pago',
        'valor_pago',
        'beneficiario_tipo',
        'beneficiario_id',
        'beneficiario_razon_social',
        'ingreso_gravado',
        'total_retenido',
        'fecha_autorizacion',
        'numero_autorizacion',
        'info_adicional',
        'dirEstablecimiento',
        'obligadoContabilidad'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_pago' => 'date',
        'fecha_autorizacion' => 'datetime',
        'total_retenido' => 'decimal:2',
        'valor_pago' => 'decimal:2',
        'ingreso_gravado' => 'decimal:2',
        'info_adicional' => 'array'
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
        return $this->hasMany(RetencionDetalle::class);
    }

    public function estados(): HasMany
    {
        return $this->hasMany(RetencionEstado::class);
    }

    public function detallesAdicionales(): HasMany
    {
        return $this->hasMany(RetencionDetalleAdicional::class);
    }
    // Atributos computados
    public function getNumeroCompletoAttribute(): string
    {
        return "{$this->estab}-{$this->pto_emi}-{$this->secuencial}";
    }

    // Métodos
    public function puedeAnular(): bool
    {
        return in_array($this->estado, ['CREADA', 'AUTORIZADA']);
    }

    public function actualizarEstado(string $estado, string $usuario, ?array $data = null): void
    {
        $this->estados()->create([
            'estado_actual' => $estado,
            'usuario_proceso' => $usuario,
            'ip_origen' => request()->ip(),
            'data' => $data
        ]);

        $this->update(['estado' => $estado]);
    }
}
