<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // Constantes seg�n cat�logo SRI
    const FORMAS_PAGO = [
        '01' => 'Sin utilizaci�n del sistema financiero',
        '15' => 'Compensaci�n de deudas',
        '16' => 'Tarjeta de d�bito',
        '17' => 'Dinero electr�nico',
        '18' => 'Tarjeta prepago',
        '19' => 'Tarjeta de cr�dito',
        '20' => 'Otros con utilizaci�n del sistema financiero',
        '21' => 'Endoso de t�tulos',
        // Formas de pago v2.1.0
        '22' => 'Giro',
        '23' => 'Dep�sito en cuenta',
        '24' => 'Transferencia bancaria',
        '25' => 'Tarjeta de regalo o vale'
    ];

    // Relaci�n con los pagos de facturas
    public function facturaPagos()
    {
        return $this->hasMany(FacturaPago::class, 'formaPago', 'codigo');
    }

    // M�todo para obtener formas de pago por versi�n
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