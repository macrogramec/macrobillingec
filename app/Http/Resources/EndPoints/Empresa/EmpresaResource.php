<?php

namespace App\Http\Resources\EndPoints\Empresa;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="EmpresaResource",
 *     title="Empresa Resource",
 *     description="Recurso de empresa",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ruc", type="string", example="0992877878001"),
 *     @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
 *     @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
 *     @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
 *     @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
 *     @OA\Property(property="contribuyente_especial", type="string", example="12345"),
 *     @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
 *     @OA\Property(property="tipo_emision", type="string", enum={"normal", "contingencia"}, example="normal"),
 *     @OA\Property(
 *         property="correos_notificacion",
 *         type="array",
 *         @OA\Items(type="string", example="correo@ejemplo.com")
 *     ),
 *     @OA\Property(property="regimen_microempresas", type="boolean", example=false),
 *     @OA\Property(property="agente_retencion", type="string", example="1"),
 *     @OA\Property(property="rimpe", type="boolean", example=false),
 *     @OA\Property(property="tiene_firma", type="boolean", example=true),
 *     @OA\Property(property="fecha_vencimiento_firma", type="string", format="date", example="2024-12-31"),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01 00:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-01 00:00:00")
 * )
 */
class EmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ruc' => $this->ruc,
            'razon_social' => $this->razon_social,
            'nombre_comercial' => $this->nombre_comercial,
            'direccion_matriz' => $this->direccion_matriz,
            'obligado_contabilidad' => $this->obligado_contabilidad,
            'contribuyente_especial' => $this->contribuyente_especial,
            'ambiente' => $this->ambiente,
            'tipo_emision' => $this->tipo_emision,
            'correos_notificacion' => $this->correos_notificacion,
            'regimen_microempresas' => $this->regimen_microempresas,
            'agente_retencion' => $this->agente_retencion,
            'rimpe' => $this->rimpe,
            'tiene_firma' => !is_null($this->firma_electronica),
            'fecha_vencimiento_firma' => $this->fecha_vencimiento_firma?->format('Y-m-d'),
            'establecimientos' => EstablecimientoResource::collection($this->whenLoaded('establecimientos')),
           // 'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'usuario_macrobilling' => $this->usuario_macrobilling,
            //'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
