<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
class GuiaRemision extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guias_remision';

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
        'dirEstablecimiento',
        'dirPartida',
        'razonSocialTransportista',
        'tipoIdentificacionTransportista',
        'rucTransportista',
        'rise',
        'obligadoContabilidad',
        'contribuyenteEspecial',
        'fechaIniTransporte',
        'fechaFinTransporte',
        'placa',
        'procesadoSri',
        'fechaAutorizacion',
        'numeroAutorizacion',
        'ambienteAutorizacion',
        'infoAdicional',
        'version_actual',
        'historial_cambios'
    ];

    protected $casts = [
        'fechaAutorizacion' => 'datetime',
        'infoAdicional' => 'array',
        'historial_cambios' => 'array',
        'procesadoSri' => 'boolean'
    ];


    const ESTADO = [
        'CREADA' => 'CREADA',
        'FIRMADA' => 'FIRMADA',
        'ENVIADA' => 'ENVIADA',
        'AUTORIZADA' => 'AUTORIZADA',
        'RECHAZADA' => 'RECHAZADA',
        'ANULADA' => 'ANULADA'
    ];

    const AMBIENTE = [
        '1' => 'PRUEBAS',
        '2' => 'PRODUCCION'
    ];

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

    public function destinatarios()
    {
        return $this->hasMany(GuiaRemisionDestinatario::class);
    }

    public function estados()
    {
        return $this->hasMany(GuiaRemisionEstado::class);
    }

    public function getNumeroCompletoAttribute()
    {
        return "{$this->estab}-{$this->ptoEmi}-{$this->secuencial}";
    }

    public function generarPDF()
    {
        // Implementación para generar PDF
        return null;
    }

    public function generarXML()
    {
        // Implementación para generar XML
        return '';
    }
}
