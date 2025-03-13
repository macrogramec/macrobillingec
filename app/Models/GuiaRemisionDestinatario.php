<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuiaRemisionDestinatario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guia_remision_destinatarios';

    protected $fillable = [
        'guia_remision_id',
        'identificacionDestinatario',
        'razonSocialDestinatario',
        'dirDestinatario',
        'motivoTraslado',
        'docAduaneroUnico',
        'codEstabDestino',
        'ruta',
        'codDocSustento',
        'numDocSustento',
        'numAutDocSustento',
        'fechaEmisionDocSustento'
    ];

    protected $casts = [
    ];

    public function guiaRemision()
    {
        return $this->belongsTo(GuiaRemision::class);
    }

    public function detalles()
    {
        return $this->hasMany(GuiaRemisionDetalle::class);
    }
}
