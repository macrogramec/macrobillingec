<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PuntoEmision extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'puntos_emision';

    protected $fillable = [
        'uuid',
        'establecimiento_id',
        'codigo',
        'tipo_comprobante',
        'comprobante',
        'secuencial_actual',
        'estado',
        'ambiente',
        'secuencias',
        'identificador_externo',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'secuencias' => 'array'
    ];

    // Relaciones
    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function historialSecuenciales()
    {
        return $this->hasMany(HistorialSecuencial::class);
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
