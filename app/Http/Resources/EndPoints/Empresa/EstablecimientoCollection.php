<?php

namespace App\Http\Resources\EndPoints\Empresa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="EstablecimientoCollection",
 *     title="Establecimiento Collection",
 *     description="Colección de establecimientos",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/EstablecimientoResource")
 *     ),
 *     @OA\Property(property="meta", type="object"),
 *     @OA\Property(property="links", type="object")
 * )
 */
class EstablecimientoCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
