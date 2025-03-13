<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RetencionDetalleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'linea' => $this->linea,
            'codigo_retencion' => [
                'id' => $this->codigo_retencion_id,
                'codigo' => $this->codigoRetencion->codigo,
                'concepto' => $this->codigoRetencion->concepto
            ],
            'base_imponible' => $this->base_imponible,
            'porcentaje_retener' => $this->porcentaje_retener,
            'valor_retenido' => $this->valor_retenido,

            'documento_sustento' => [
                'codigo' => $this->cod_doc_sustento,
                'numero' => $this->num_doc_sustento,
                'fecha_emision' => $this->fecha_emision_doc_sustento->format('Y-m-d')
            ],

            'valores_dividendos' => $this->when($this->utilidad_antes_ir !== null, [
                'utilidad_antes_ir' => $this->utilidad_antes_ir,
                'impuesto_renta_sociedad' => $this->impuesto_renta_sociedad,
                'utilidad_efectiva' => $this->utilidad_efectiva
            ])
        ];
    }
}
