<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'estado',
        'version',
        'empresa_id',
        'establecimiento_id',
        'punto_emision_id',
        'ambiente',
        'tipoEmision',
        'razonSocial',
        'nombreComercial',
        'ruc',
        'claveAcceso',
        'codDoc',
        'estab',
        'ptoEmi',
        'secuencial',
        'dirMatriz',
        'fechaEmision',
        'dirEstablecimiento',
        'contribuyenteEspecial',
        'obligadoContabilidad',
        'comercioExterior',
        'incoTermFactura',
        'lugarIncoTerm',
        'paisOrigen',
        'puertoEmbarque',
        'puertoDestino',
        'paisDestino',
        'paisAdquisicion',
        'tipoIdentificacionComprador',
        'guiaRemision',
        'razonSocialComprador',
        'identificacionComprador',
        'direccionComprador',
        'emailComprador',
        'placa',
        'totalSinImpuestos',
        'totalSubsidio',
        'incoTermTotal',
        'totalDescuento',
        'totalDescuentoAdicional',
        'totalSinImpuestosSinIce',
        'totalImpuestos',
        'totalExonerado',
        'totalIce',
        'totalIva',
        'totalIRBPNR',
        'propina',
        'fleteInternacional',
        'seguroInternacional',
        'gastosAduaneros',
        'gastosTransporteOtros',
        'importeTotal',
        'moneda',
        'valorRetIva',
        'valorRetRenta',
        'pagos_total',
        'pagos_total_anticipos',
        'pagos_saldo_pendiente',
        'regimenFiscal',
        'agenteRetencion',
        'contratoProveedorEstado',
        'maquinaFiscal',
        'tipoProveedorRegimenMicroempresas',
        'digitoVerificador',
        'fechaAutorizacion',
        'numeroAutorizacion',
        'ambienteAutorizacion',
        'infoAdicional',
        'motivos',
        'procesadoSri',
        'version_actual',
        'historial_cambios'
    ];

    protected $casts = [
        'fechaEmision' => 'string',
        'fechaAutorizacion' => 'datetime',
        'infoAdicional' => 'array',
        'motivos' => 'array',
        'historial_cambios' => 'array',
        'procesadoSri' => 'boolean'
    ];

    const VERSIONES_SOPORTADAS = [
        '1.0.0' => 'Versión Original',
        '1.1.0' => 'Primera Actualización',
        '2.0.0' => 'Segunda Versión',
        '2.1.0' => 'Última Versión'
    ];

    const TIPO_IDENTIFICACION = [
        '04' => 'RUC',
        '05' => 'CEDULA',
        '06' => 'PASAPORTE',
        '07' => 'CONSUMIDOR FINAL',
        '08' => 'IDENTIFICACION DEL EXTERIOR',
        '09' => 'PLACA'
    ];

    const ESTADO = [
        'CREADA' => 'CREADA',
        'FIRMADA' => 'FIRMADA',
        'ENVIADA' => 'ENVIADA',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA'
    ];

    const AMBIENTE = [
        '1' => 'PRUEBAS',
        '2' => 'PRODUCCION'
    ];

    const COMERCIO_EXTERIOR = [
        'SI' => 'EXPORTADOR',
        'NO' => 'MERCADO INTERNO'
    ];

    const TIPO_PROVEEDOR_RIMPE = [
        '01' => 'Contribuyente Régimen RIMPE',
        '02' => 'No Contribuyente Régimen RIMPE'
    ];

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class);
    }

    public function impuestos()
    {
        return $this->hasMany(FacturaImpuesto::class);
    }

    public function pagos()
    {
        return $this->hasMany(FacturaPago::class);
    }

    public function detallesAdicionales()
    {
        return $this->hasMany(FacturaDetalleAdicional::class);
    }

    public function estados()
    {
        return $this->hasMany(FacturaEstado::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class);
    }
}
