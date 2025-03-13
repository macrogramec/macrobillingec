<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialSecuencial extends Model
{
    use HasFactory;

    protected $table = 'historial_secuenciales';

    protected $fillable = [
        'punto_emision_id',
        'tipo_comprobante',
        'secuencial_anterior',
        'secuencial_nuevo',
        'motivo',
        'created_by'
    ];

    // Relaciones
    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
