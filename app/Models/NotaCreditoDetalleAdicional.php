<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para gestionar la información adicional de las Notas de Crédito
 *
 * Este modelo maneja los campos adicionales que pueden incluirse en una nota de crédito,
 * como información personalizada o campos requeridos por el cliente.
 *
 * @property int $id Identificador único del detalle adicional
 * @property int $nota_credito_id ID de la nota de crédito relacionada
 * @property string $nombre Nombre del campo adicional
 * @property string $valor Valor del campo adicional
 * @property int $orden Orden de aparición en el documento
 * @property string $version Versión del formato XML
 * @property string|null $usuario_creacion Usuario que creó el registro
 * @property string|null $ip_creacion IP desde donde se creó el registro
 */
class NotaCreditoDetalleAdicional extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla en la base de datos
     * @var string
     */
    protected $table = 'nota_credito_detalles_adicionales';

    /**
     * Campos que se pueden llenar masivamente
     * @var array
     */
    protected $fillable = [
        'nota_credito_id',
        'nombre',
        'valor',
        'orden',
        'version',
        'usuario_creacion',
        'ip_creacion'
    ];

    /**
     * Validaciones del modelo
     * @var array
     */
    public static $rules = [
        'nombre' => 'required|string|max:300',
        'valor' => 'required|string|max:300',
        'orden' => 'required|integer|min:0'
    ];

    /**
     * Relación con la nota de crédito
     */
    public function notaCredito(): BelongsTo
    {
        return $this->belongsTo(NotaCredito::class);
    }

    /**
     * Convierte el detalle adicional a formato XML
     */
    public function toXML(): array
    {
        return [
            'nombre' => $this->nombre,
            'valor' => $this->valor
        ];
    }

    /**
     * Valida la longitud máxima permitida por el SRI
     */
    public function validarLongitud(): bool
    {
        return strlen($this->nombre) <= 300 && strlen($this->valor) <= 300;
    }

    /**
     * Método estático para crear múltiples detalles adicionales
     */
    public static function crearMultiples(int $notaCreditoId, array $detalles): array
    {
        $creados = [];
        $orden = 0;

        foreach ($detalles as $detalle) {
            $creados[] = static::create([
                'nota_credito_id' => $notaCreditoId,
                'nombre' => $detalle['nombre'],
                'valor' => $detalle['valor'],
                'orden' => $orden++,
                'version' => $detalle['version'] ?? '2.1.0',
                'usuario_creacion' => $detalle['usuario_creacion'] ?? auth()->user()->name,
                'ip_creacion' => request()->ip()
            ]);
        }

        return $creados;
    }

    /**
     * Actualiza el valor de un detalle adicional
     */
    public function actualizarValor(string $valor): bool
    {
        if (strlen($valor) > 300) {
            return false;
        }

        $this->valor = $valor;
        return $this->save();
    }

    /**
     * Obtiene detalles adicionales ordenados por nota de crédito
     */
    public static function obtenerPorNotaCredito(int $notaCreditoId): array
    {
        return static::where('nota_credito_id', $notaCreditoId)
            ->orderBy('orden')
            ->get()
            ->toArray();
    }

    /**
     * Prepara los detalles para el formato XML del SRI
     */
    public static function prepararParaXML(array $detalles): array
    {
        return array_map(function ($detalle) {
            return [
                'nombre' => $detalle['nombre'],
                'valor' => $detalle['valor']
            ];
        }, $detalles);
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->usuario_creacion)) {
                $model->usuario_creacion = auth()->user()->name ?? 'Sistema';
            }
            if (empty($model->ip_creacion)) {
                $model->ip_creacion = request()->ip();
            }
        });
    }
}
