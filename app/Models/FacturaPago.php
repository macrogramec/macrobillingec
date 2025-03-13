<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaPago extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'factura_id',
        'formaPago',
        'total',
        'plazo',
        'unidadTiempo',
        'institucionFinanciera',
        'numeroCuenta',
        'numeroTarjeta',
        'propietarioTarjeta',
        'version'
    ];

    const UNIDADES_TIEMPO = [
        'dias' => 'Días',
        'meses' => 'Meses',
        'anios' => 'Años'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function formaPagoCatalogo()
    {
        return $this->belongsTo(FormaPago::class, 'formaPago', 'codigo');
    }

    public function requiereInfoBancaria()
    {
        return in_array($this->formaPago, ['19', '20', '23', '24']);
    }

    public function requierePlazo()
    {
        return in_array($this->formaPago, ['19', '20', '21']);
    }

    public function toXML()
    {
        $xml = [
            'formaPago' => $this->formaPago,
            'total' => number_format($this->total, 2, '.', '')
        ];

        if ($this->plazo) {
            $xml['plazo'] = $this->plazo;
            $xml['unidadTiempo'] = strtoupper($this->unidadTiempo);
        }

        if ($this->version >= '2.1.0' && $this->requiereInfoBancaria()) {
            foreach (['institucionFinanciera', 'numeroCuenta', 'numeroTarjeta', 'propietarioTarjeta'] as $campo) {
                if ($this->$campo) {
                    $xml[$campo] = $this->$campo;
                }
            }
        }

        return $xml;
    }
}
