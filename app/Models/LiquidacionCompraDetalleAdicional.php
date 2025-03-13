<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiquidacionCompraDetalleAdicional extends Model
{
    use HasFactory;
    protected $table = 'liquidacion_compra_detalles_adicionales';
    protected $fillable = [
        'liquidacion_compra_id',
        'nombre',
        'valor',
        'orden',
        'version',
        'activo',
        'usuario_creacion',
        'ip_creacion',
        'usuario_modificacion',
        'ip_modificacion'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function liquidacionCompra(): BelongsTo
    {
        return $this->belongsTo(LiquidacionCompra::class);
    }

    public static function crearMultiples(int $liquidacionCompraId, array $detalles): array
    {
        $creados = [];
        $orden = 0;

        foreach ($detalles as $detalle) {
            $creados[] = static::create([
                'liquidacion_compra_id' => $liquidacionCompraId,
                'nombre' => $detalle['nombre'],
                'valor' => $detalle['valor'],
                'orden' => $orden++,
                'version' => $detalle['version'] ?? '1.1.0',
                'usuario_creacion' => auth()->user()->name ?? 'Sistema',
                'ip_creacion' => request()->ip()
            ]);
        }

        return $creados;
    }
}
