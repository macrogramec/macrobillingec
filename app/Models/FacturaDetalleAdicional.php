<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaDetalleAdicional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'factura_detalles_adicionales';

    protected $fillable = [
        'factura_id',
        'nombre',
        'valor',
        'orden',
        'version',
        'usuario_creacion',
        'ip_creacion'
    ];

    /**
     * Get the factura that owns the detalle adicional.
     */
    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Convertir el detalle adicional a formato XML.
     *
     * @return array
     */
    public function toXML()
    {
        return [
            'nombre' => $this->nombre,
            'valor' => $this->valor
        ];
    }

    /**
     * Validar longitud máxima según el SRI.
     *
     * @return bool
     */
    public function validarLongitud()
    {
        return strlen($this->nombre) <= 300 && strlen($this->valor) <= 300;
    }

    /**
     * Método estático para crear múltiples detalles adicionales.
     *
     * @param int $facturaId
     * @param array $detalles
     * @return Collection
     */
    public static function crearMultiples($facturaId, array $detalles)
    {
        $orden = 1;
        $creados = collect();

        foreach ($detalles as $detalle) {
            $creados->push(self::create([
                'factura_id' => $facturaId,
                'nombre' => $detalle['nombre'],
                'valor' => $detalle['valor'],
                'orden' => $orden++,
                'version' => $detalle['version'] ?? '2.1.0',
                'usuario_creacion' => $detalle['usuario_creacion'] ?? auth()->id(),
                'ip_creacion' => request()->ip()
            ]));
        }

        return $creados;
    }

    /**
     * Actualizar el valor de un detalle adicional.
     *
     * @param string $valor
     * @return bool
     */
    public function actualizarValor($valor)
    {
        if (strlen($valor) > 300) {
            return false;
        }

        $this->valor = $valor;
        return $this->save();
    }

    /**
     * Obtener detalles adicionales por factura ordenados.
     *
     * @param int $facturaId
     * @return Collection
     */
    public static function obtenerPorFactura($facturaId)
    {
        return self::where('factura_id', $facturaId)
                   ->orderBy('orden')
                   ->get();
    }

    /**
     * Convertir colección de detalles a formato para XML del SRI.
     *
     * @param Collection $detalles
     * @return array
     */
    public static function prepararParaXML($detalles)
    {
        return $detalles->map(function ($detalle) {
            return [
                'nombre' => $detalle->nombre,
                'valor' => $detalle->valor
            ];
        })->toArray();
    }
}