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
        'fechaEmision' => 'date',
        'fechaAutorizacion' => 'datetime',
        'infoAdicional' => 'array',
        'motivos' => 'array',
        'historial_cambios' => 'array',
        'procesadoSri' => 'boolean'
    ];

    // Constantes para versiones soportadas
    const VERSIONES_SOPORTADAS = [
        '1.0.0' => 'Versión Original',
        '1.1.0' => 'Primera Actualización',
        '2.0.0' => 'Segunda Versión',
        '2.1.0' => 'Última Versión'
    ];

    // Constantes del SRI
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

    // Método para validar campos según la versión
    public function validarVersionCampos()
    {
        if ($this->version >= '2.1.0') {
            // Validaciones específicas para v2.1.0
            return $this->validarV210();
        } elseif ($this->version >= '2.0.0') {
            // Validaciones específicas para v2.0.0
            return $this->validarV200();
        } else {
            // Validaciones para versiones 1.x.x
            return $this->validarV100();
        }
    }

    // Método para generar XML según la versión
    public function generarXML()
    {
        if ($this->version >= '2.1.0') {
            return $this->generarXMLV210();
        } elseif ($this->version >= '2.0.0') {
            return $this->generarXMLV200();
        } else {
            return $this->generarXMLV100();
        }
    }

    // Generación de clave de acceso (mismo método que antes)
    public function generarClaveAcceso()
    {
        // ... código existente ...
    }
}