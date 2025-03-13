<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaPago extends Model
{
    use HasFactory;

    protected $table = 'formas_pago';

    protected $fillable = [
        'codigo',
        'descripcion',
        'requiere_plazo',
        'requiere_banco',
        'activo',
        'version_desde',
        'version_hasta'
    ];

    protected $casts = [
        'requiere_plazo' => 'boolean',
        'requiere_banco' => 'boolean',
        'activo' => 'boolean'
    ];

    const FORMAS_PAGO = [
        '01' => 'Sin utilización del sistema financiero',
        '15' => 'Compensación de deudas',
        '16' => 'Tarjeta de débito',
        '17' => 'Dinero electrónico',
        '18' => 'Tarjeta prepago',
        '19' => 'Tarjeta de crédito',
        '20' => 'Otros con utilización del sistema financiero',
        '21' => 'Endoso de títulos',
        '22' => 'Giro',
        '23' => 'Depósito en cuenta',
        '24' => 'Transferencia bancaria',
        '25' => 'Tarjeta de regalo o vale'
    ];

    public function facturaPagos()
    {
        return $this->hasMany(FacturaPago::class, 'formaPago', 'codigo');
    }

    public static function getFormasPagoPorVersion($version)
    {
        return self::where('version_desde', '<=', $version)
            ->where(function ($query) use ($version) {
                $query->where('version_hasta', '>=', $version)
                    ->orWhereNull('version_hasta');
            })
            ->where('activo', true)
            ->get();
    }
}
