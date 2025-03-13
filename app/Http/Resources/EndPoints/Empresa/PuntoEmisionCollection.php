<?php

namespace App\Http\Resources\EndPoints\Empresa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="PuntoEmisionCollection",
 *     title="Punto de Emisión Collection",
 *     description="Colección de puntos de emisión",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PuntoEmisionResource")
 *     ),
 *     @OA\Property(property="meta", type="object"),
 *     @OA\Property(property="links", type="object")
 * )
 */
class PuntoEmisionCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
