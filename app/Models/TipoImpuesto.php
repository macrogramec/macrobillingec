<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class TipoImpuesto extends Model
{
    //
    protected $table = 'tipos_impuestos';
    protected $primaryKey = 'codigo';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'codigo_sri',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function tarifas(): HasMany
    {
        return $this->hasMany(TarifaImpuesto::class, 'tipo_impuesto_codigo', 'codigo');
    }

    public function tarifasVigentes()
    {
        return $this->tarifas()
            ->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where(function ($query) {
                $query->where('fecha_fin', '>=', now())
                    ->orWhereNull('fecha_fin');
            });
    }

    public static function getPorCodigosSRI(string $tipoCodigoSRI, string $tarifaCodigoSRI)
    {
        Log::info('Buscando tarifa por códigos SRI:', [
            'tipo_codigo_sri' => $tipoCodigoSRI,
            'tarifa_codigo_sri' => $tarifaCodigoSRI
        ]);

        try {
            // Primero buscar el tipo de impuesto por código SRI
            $tipoImpuesto = TipoImpuesto::where('codigo_sri', $tipoCodigoSRI)
                ->where('activo', true)
                ->first();

            Log::info('Tipo de impuesto encontrado:', ['tipo_impuesto' => $tipoImpuesto]);

            if (!$tipoImpuesto) {
                Log::warning('Tipo de impuesto no encontrado para código SRI: ' . $tipoCodigoSRI);
                return null;
            }

            // Luego buscar la tarifa que corresponde
            $tarifa = static::where('tipo_impuesto_codigo', $tipoImpuesto->codigo)
                ->where('codigo_sri', $tarifaCodigoSRI)
                ->where('activo', true)
                ->where('fecha_inicio', '<=', now())
                ->where(function ($query) {
                    $query->where('fecha_fin', '>=', now())
                        ->orWhereNull('fecha_fin');
                })
                ->first();

            Log::info('Tarifa encontrada:', ['tarifa' => $tarifa]);

            return $tarifa;

        } catch (\Exception $e) {
            Log::error('Error al buscar tarifa por códigos SRI:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    public static function validarCodigosSRI(string $tipoCodigoSRI, string $tarifaCodigoSRI): bool
    {
        $tarifa = self::getPorCodigosSRI($tipoCodigoSRI, $tarifaCodigoSRI);
        return $tarifa !== null && $tarifa->estaVigente();
    }
}
