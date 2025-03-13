<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RetencionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado,
            'ambiente' => $this->ambiente,
            'numero_completo' => $this->numero_completo,
            'clave_acceso' => $this->clave_acceso,
            'fecha_emision' => $this->fecha_emision->format('Y-m-d'),
            'periodo_fiscal' => $this->periodo_fiscal,

            'emisor' => [
                'ruc' => $this->ruc,
                'razon_social' => $this->razon_social,
                'nombre_comercial' => $this->nombre_comercial,
                'direccion_matriz' => $this->dir_matriz
            ],

            'sujeto_retenido' => [
                'tipo_identificacion' => $this->tipo_identificacion_sujeto,
                'identificacion' => $this->identificacion_sujeto,
                'razon_social' => $this->razon_social_sujeto,
                'tipo_sujeto' => $this->tipo_sujeto,
                'regimen' => $this->regimen_sujeto
            ],

            'totales' => [
                'total_retenido' => $this->total_retenido
            ],

            'detalles' => RetencionDetalleResource::collection($this->whenLoaded('detalles')),
            'estados' => RetencionEstadoResource::collection($this->whenLoaded('estados')),

            'autorizacion' => $this->when($this->estado === 'AUTORIZADA', [
                'numero' => $this->numero_autorizacion,
                'fecha' => $this->fecha_autorizacion?->format('Y-m-d H:i:s')
            ]),

            'info_adicional' => $this->info_adicional,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
