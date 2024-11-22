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
        'detallesAdicionales' => 'array'
    ];

    // Constantes para tipos de impuestos
    const IMPUESTO_IVA = [
        '0' => '0%',
        '2' => '12%',
        '3' => '14%',
        '6' => 'No Objeto de Impuesto',
        '7' => 'Exento de IVA'
    ];

    // Catálogo para unidades de medida v2.1.0
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

    // Calcula el valor del IVA
    public function calcularIVA()
    {
        return $this->impuesto_baseImponible * ($this->impuesto_tarifa / 100);
    }

    // Calcula el subtotal con descuento
    public function calcularSubtotal()
    {
        return ($this->cantidad * $this->precioUnitario) - $this->descuento;
    }

    // Validaciones específicas según versión
    public function validarVersion()
    {
        switch($this->version) {
            case '2.1.0':
                return $this->validarV210();
            case '2.0.0':
                return $this->validarV200();
            default:
                return $this->validarV100();
        }
    }

    // Método para generar la estructura XML según la versión
    public function toXML()
    {
        switch($this->version) {
            case '2.1.0':
                return $this->toXMLV210();
            case '2.0.0':
                return $this->toXMLV200();
            default:
                return $this->toXMLV100();
        }
    }

    // Agregar detalle adicional
    public function agregarDetalleAdicional($nombre, $valor)
    {
        $detalles = $this->detallesAdicionales ?? [];
        $detalles[] = [
            'nombre' => $nombre,
            'valor' => $valor
        ];
        $this->detallesAdicionales = $detalles;
        $this->save();
    }
}