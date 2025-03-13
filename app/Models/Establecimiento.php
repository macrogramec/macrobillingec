<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Establecimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'establecimientos';

    protected $fillable = [
        'uuid',
        'empresa_id',
        'codigo',
        'direccion',
        'nombre_comercial',
        'estado',
        'ambiente',
        'correos_establecimiento',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'correos_establecimiento' => 'array'
    ];

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function puntosEmision()
    {
        return $this->hasMany(PuntoEmision::class);
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
