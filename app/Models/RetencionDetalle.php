<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @OA\Schema(
 *     schema="RetencionDetalle",
 *     title="Detalle de Retención",
 *     description="Modelo para los detalles de una retención",
 *     required={"retencion_id", "codigo_retencion_id", "base_imponible", "porcentaje_retener"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="retencion_id", type="integer", description="ID de la retención padre"),
 *     @OA\Property(property="linea", type="integer", description="Número de línea del detalle"),
 *     @OA\Property(property="codigo_retencion_id", type="integer", description="ID del código de retención"),
 *     @OA\Property(property="base_imponible", type="number", format="decimal", example=100.00),
 *     @OA\Property(property="porcentaje_retener", type="number", format="decimal", example=10.00),
 *     @OA\Property(property="valor_retenido", type="number", format="decimal", example=10.00),
 *     @OA\Property(property="cod_doc_sustento", type="string", description="Código del documento de sustento"),
 *     @OA\Property(property="num_doc_sustento", type="string", description="Número del documento de sustento"),
 *     @OA\Property(property="fecha_emision_doc_sustento", type="string", format="date"),
 *     @OA\Property(property="utilidad_antes_ir", type="number", format="decimal", nullable=true),
 *     @OA\Property(property="impuesto_renta_sociedad", type="number", format="decimal", nullable=true),
 *     @OA\Property(property="utilidad_efectiva", type="number", format="decimal", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class RetencionDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retencion_detalles';

    protected $fillable = [
        'retencion_id',
        'linea',
        'codigo',
        'tipo_impuesto',
        'codigo_retencion_id',
        'base_imponible',
        'porcentaje_retener',
        'valor_retenido',
        'cod_doc_sustento',
        'num_doc_sustento',
        'fecha_emision_doc_sustento',
        'utilidad_antes_ir',
        'impuesto_renta_sociedad',
        'utilidad_efectiva'
    ];

    protected $casts = [
        'base_imponible' => 'decimal:2',
        'porcentaje_retener' => 'decimal:2',
        'valor_retenido' => 'decimal:2',
        'utilidad_antes_ir' => 'decimal:2',
        'impuesto_renta_sociedad' => 'decimal:2',
        'utilidad_efectiva' => 'decimal:2',
        'fecha_emision_doc_sustento' =>  'date:Y-m-d'
    ];

    public function retencion(): BelongsTo
    {
        return $this->belongsTo(Retencion::class);
    }

    public function codigoRetencion(): BelongsTo
    {
        return $this->belongsTo(CodigoRetencion::class);
    }

    public function calcularValorRetenido(): float
    {
        return round(($this->base_imponible * $this->porcentaje_retener) / 100, 2);
    }
}
