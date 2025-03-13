<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LiquidacionCompraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'estado' => $this->estado,
            'version' => $this->version,

            // Datos de control
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones principales
            'empresa' => [
                'id' => $this->empresa_id,
                'razon_social' => $this->razon_social,
                'nombre_comercial' => $this->nombre_comercial,
                'ruc' => $this->ruc,
                'direccion_matriz' => $this->dir_matriz,
            ],

            'establecimiento' => [
                'id' => $this->establecimiento_id,
                'codigo' => $this->estab,
                'direccion' => $this->dir_establecimiento,
            ],

            'punto_emision' => [
                'id' => $this->punto_emision_id,
                'codigo' => $this->pto_emi,
            ],

            // Datos del documento
            'ambiente' => $this->ambiente,
            'tipo_emision' => $this->tipo_emision,
            'secuencial' => $this->secuencial,
            'clave_acceso' => $this->clave_acceso,
            'fecha_emision' => $this->fecha_emision,
            'periodo_fiscal' => $this->periodo_fiscal,

            // Datos del proveedor
            'proveedor' => [
                'tipo_identificacion' => $this->tipo_identificacion_proveedor,
                'identificacion' => $this->identificacion_proveedor,
                'razon_social' => $this->razon_social_proveedor,
                'direccion' => $this->direccion_proveedor,
                'tipo' => $this->tipo_proveedor,
            ],

            // Totales
            'totales' => [
                'sin_impuestos' => $this->total_sin_impuestos,
                'descuento' => $this->total_descuento,
                'ice' => $this->total_ice,
                'iva' => $this->total_iva,
                'irbpnr' => $this->total_irbpnr,
                'sin_impuestos_sin_ice' => $this->total_sin_impuestos_sin_ice,
                'impuestos' => $this->total_impuestos,
                'importe_total' => $this->importe_total,
            ],

            // Estado SRI
            'autorizacion_sri' => $this->when($this->procesado_sri, [
                'fecha' => $this->fecha_autorizacion,
                'numero' => $this->numero_autorizacion,
                'ambiente' => $this->ambiente_autorizacion,
            ]),

            // Información adicional
            'info_adicional' => $this->info_adicional,

            // Relaciones
            'detalles' => DetalleResource::collection($this->whenLoaded('detalles')),
            'retenciones' => RetencionResource::collection($this->whenLoaded('retenciones')),
            'estados' => EstadoResource::collection($this->whenLoaded('estados')),

            // Enlaces
            'links' => [
                'self' => route('api.liquidaciones.show', $this->id),
                'pdf' => $this->when($this->procesado_sri, route('api.liquidaciones.pdf', $this->id)),
                'xml' => $this->when($this->procesado_sri, route('api.liquidaciones.xml', $this->id)),
            ],
        ];
    }

    /**
     * Collection class for wrapping multiple items.
     */
    public function collection($request)
    {
        return [
            'data' => $this->collection,
            'links' => [
                'self' => route('api.liquidaciones.index'),
            ],
            'meta' => [
                'total_registros' => $this->collection->count(),
                'total_autorizadas' => $this->collection->where('procesado_sri', true)->count(),
                'total_pendientes' => $this->collection->where('procesado_sri', false)->count(),
            ],
        ];
    }
}

class DetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo_principal' => $this->codigo_principal,
            'codigo_auxiliar' => $this->codigo_auxiliar,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'descuento' => $this->descuento,
            'precio_total_sin_impuesto' => $this->precio_total_sin_impuesto,
            'detalles_adicionales' => $this->detalles_adicionales,
            'impuestos' => ImpuestoResource::collection($this->whenLoaded('impuestos')),
        ];
    }
}

class ImpuestoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'codigo_porcentaje' => $this->codigo_porcentaje,
            'tarifa' => $this->tarifa,
            'base_imponible' => $this->base_imponible,
            'valor' => $this->valor,
        ];
    }
}

class RetencionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'codigo_porcentaje' => $this->codigo_porcentaje,
            'tarifa' => $this->tarifa,
            'base_imponible' => $this->base_imponible,
            'valor_retenido' => $this->valor_retenido,
        ];
    }
}

class EstadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado_anterior' => $this->estado_anterior,
            'estado_actual' => $this->estado_actual,
            'observacion' => $this->observacion,
            'metadata' => $this->metadata,
            'usuario' => $this->whenLoaded('usuario', fn() => [
                'id' => $this->usuario->id,
                'nombre' => $this->usuario->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
