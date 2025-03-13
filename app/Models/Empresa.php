<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'uuid',
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion_matriz',
        'obligado_contabilidad',
        'contribuyente_especial',
        'ambiente',
        'tipo_emision',
        'correos_notificacion',
        'logo',
        'regimen_microempresas',
        'agente_retencion',
        'rimpe',
        'firma_electronica',
        'clave_firma',
        'created_by',
        'updated_by',
        'usuario_macrobilling'
    ];

    protected $casts = [
        'obligado_contabilidad' => 'boolean',
        'regimen_microempresas' => 'boolean',
        'rimpe' => 'boolean',
        'correos_notificacion' => 'array',
        'fecha_vencimiento_firma' => 'datetime'
    ];

    protected $hidden = [
        'firma_electronica',
        'clave_firma'
    ];

    // Relaciones
    public function establecimientos()
    {
        return $this->hasMany(Establecimiento::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
