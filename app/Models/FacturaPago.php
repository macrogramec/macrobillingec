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
        'dias' => 'D�as',
        'meses' => 'Meses',
        'anios' => 'A�os'
    ];

    // Relaci�n con la factura
    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    // Relaci�n con la forma de pago
    public function formaPagoCatalogo()
    {
        return $this->belongsTo(FormaPago::class, 'formaPago', 'codigo');
    }

    // Validar si requiere informaci�n bancaria
    public function requiereInfoBancaria()
    {
        $formasPagoConBanco = ['19', '20', '23', '24']; // Tarjetas, transferencias, dep�sitos
        return in_array($this->formaPago, $formasPagoConBanco);
    }

    // Validar si requiere plazo
    public function requierePlazo()
    {
        $formasPagoConPlazo = ['19', '20', '21']; // Cr�dito, otros con sistema financiero, endoso
        return in_array($this->formaPago, $formasPagoConPlazo);
    }

    // M�todo para generar la estructura XML seg�n versi�n
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
            if ($this->institucionFinanciera) {
                $xml['institucionFinanciera'] = $this->institucionFinanciera;
            }
            if ($this->numeroCuenta) {
                $xml['numeroCuenta'] = $this->numeroCuenta;
            }
            if ($this->numeroTarjeta) {
                $xml['numeroTarjeta'] = $this->numeroTarjeta;
            }
            if ($this->propietarioTarjeta) {
                $xml['propietarioTarjeta'] = $this->propietarioTarjeta;
            }
        }

        return $xml;
    }
}