<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuiaRemisionDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guia_remision_detalles';

    protected $fillable = [
        'guia_remision_destinatario_id',
        'codigoInterno',
        'codigoAdicional',
        'descripcion',
        'cantidad',
        'detallesAdicionales'
    ];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'detallesAdicionales' => 'array'
    ];

    public function destinatario()
    {
        return $this->belongsTo(GuiaRemisionDestinatario::class, 'guia_remision_destinatario_id');
    }
}
