<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'factura_detalles';

    protected $fillable = [
        'factura_id',
        'linea',
        'codigoPrincipal',
        'codigoAuxiliar',
        'descripcion',
        'cantidad',
        'precioUnitario',
        'precioSinSubsidio',
        'descuento',
        'precioTotalSinImpuesto',
        'detallesAdicionales',
        'impuesto_codigo',
        'impuesto_codigoPorcentaje',
        'impuesto_tarifa',
        'impuesto_baseImponible',
        'impuesto_valor',
        'ice_codigo',
        'ice_tarifa',
        'ice_baseImponible',
        'ice_valor',
        'irbpnr_codigo',
        'irbpnr_tarifa',
        'irbpnr_baseImponible',
        'irbpnr_valor',
        'unidadMedida',
        'precioUnitarioSubsidio',
        'codigoPartidaArancelaria',
        'precioReferencialUnitario',
        'valoracionTotal',
        'paisOrigen',
        'paisAdquisicion',
        'valorParteReforma',
        'version'
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'precioUnitario' => 'decimal:6',
        'precioSinSubsidio' => 'decimal:6',
        'descuento' => 'decimal:2',
        'precioTotalSinImpuesto' => 'decimal:2',
        'detallesAdicionales' => 'json',
        'impuesto_tarifa' => 'decimal:2',
        'impuesto_baseImponible' => 'decimal:2',
        'impuesto_valor' => 'decimal:2',
        'ice_tarifa' => 'decimal:2',
        'ice_baseImponible' => 'decimal:2',
        'ice_valor' => 'decimal:2',
        'irbpnr_tarifa' => 'decimal:2',
        'irbpnr_baseImponible' => 'decimal:2',
        'irbpnr_valor' => 'decimal:2',
        'precioUnitarioSubsidio' => 'decimal:6'
    ];

    const IMPUESTO_IVA = [
        '0' => '0%',
        '4' => '15%',
        '2' => '12%',
        '3' => '14%',
        '6' => 'No Objeto de Impuesto',
        '7' => 'Exento de IVA'
    ];

    const UNIDADES_MEDIDA = [
        'UNIDAD' => 'Unidad',
        'KILOGRAMO' => 'Kilogramo',
        'LITRO' => 'Litro',
        'GALON' => 'Galón',
        'METRO' => 'Metro',
        'METRO2' => 'Metro cuadrado',
        'METRO3' => 'Metro cúbico',
        'HORA' => 'Hora',
        'DIA' => 'Día',
        'ACTIVIDAD' => 'Actividad',
        'SERVICIO' => 'Servicio'
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function impuestos()
    {
        return $this->hasMany(FacturaDetalleImpuesto::class, 'factura_detalle_id');
    }

    public function ice()
    {
        return $this->hasOne(FacturaDetalleIce::class, 'factura_detalle_id');
    }

    public function irbpnr()
    {
        return $this->hasOne(FacturaDetalleIrbpnr::class, 'factura_detalle_id');
    }

    public function calcularIVA()
    {
        return $this->impuesto_baseImponible * ($this->impuesto_tarifa / 100);
    }

    public function calcularSubtotal()
    {
        return ($this->cantidad * $this->precioUnitario) - $this->descuento;
    }
}
