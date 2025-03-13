<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="RetencionDetalleAdicional",
 *     title="Detalle Adicional de Retención",
 *     description="Modelo para la información adicional de una retención",
 *     required={"retencion_id", "nombre", "valor"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="retencion_id", type="integer"),
 *     @OA\Property(property="nombre", type="string", maxLength=300),
 *     @OA\Property(property="valor", type="string", maxLength=300),
 *     @OA\Property(property="orden", type="integer", default=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class RetencionDetalleAdicional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retencion_detalles_adicionales';

    protected $fillable = [
        'retencion_id',
        'nombre',
        'valor',
        'orden'
    ];

    protected $casts = [
        'orden' => 'integer'
    ];

    /**
     * Obtiene la retención a la que pertenece el detalle adicional
     */
    public function retencion(): BelongsTo
    {
        return $this->belongsTo(Retencion::class);
    }
}
