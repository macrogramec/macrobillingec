<?php

namespace App\Http\Resources\EndPoints\Empresa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="EstablecimientoResource",
 *     title="Establecimiento Resource",
 *     description="Recurso de establecimiento",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="empresa_id", type="integer", example=1),
 *     @OA\Property(property="codigo", type="string", example="001"),
 *     @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
 *     @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
 *     @OA\Property(property="estado", type="string", enum={"activo", "inactivo"}, example="activo"),
 *     @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
 *     @OA\Property(
 *         property="correos_establecimiento",
 *         type="array",
 *         @OA\Items(type="string", example="sucursal@empresa.com")
 *     ),
 *     @OA\Property(
 *         property="puntos_emision",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PuntoEmisionResource"),
 *         description="Lista de puntos de emisión (incluida solo cuando se solicita)"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01 00:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-01 00:00:00")
 * )
 */
class EstablecimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'empresa_id' => $this->empresa_id,
            'codigo' => $this->codigo,
            'direccion' => $this->direccion,
            'nombre_comercial' => $this->nombre_comercial,
            'estado' => $this->estado,
            'ambiente' => $this->ambiente,
            'correos_establecimiento' => $this->correos_establecimiento,
            'puntos_emision' => PuntoEmisionResource::collection($this->whenLoaded('puntosEmision'))
            //'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            //'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
