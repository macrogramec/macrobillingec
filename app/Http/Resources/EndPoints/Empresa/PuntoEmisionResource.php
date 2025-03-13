<?php

namespace App\Http\Resources\EndPoints\Empresa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="PuntoEmisionResource",
 *     title="Punto de Emisión Resource",
 *     description="Recurso de punto de emisión",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="establecimiento_id", type="integer", example=1),
 *     @OA\Property(property="codigo", type="string", example="001"),
 *     @OA\Property(
 *         property="tipo_comprobante",
 *         type="string",
 *         enum={"01", "02", "03", "04", "05", "06", "07"},
 *         example="01",
 *         description="01:Factura, 02:Nota Débito, 03:Nota Crédito, 04:Guía Remisión, 05:Comp. Retención, 06:Nota Crédito, 07:Comp. Complementarios"
 *     ),
 *     @OA\Property(property="secuencial_actual", type="integer", example=1),
 *     @OA\Property(property="estado", type="string", enum={"activo", "inactivo"}, example="activo"),
 *     @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
 *     @OA\Property(property="identificador_externo", type="string", example="POS-001", nullable=true),
 *     @OA\Property(
 *         property="secuencias",
 *         type="object",
 *         @OA\Property(property="factura", type="integer", example=1),
 *         @OA\Property(property="nota_credito", type="integer", example=1),
 *         @OA\Property(property="nota_debito", type="integer", example=1),
 *         @OA\Property(property="guia_remision", type="integer", example=1),
 *         @OA\Property(property="retencion", type="integer", example=1)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01 00:00:00"),
 *     @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-01 00:00:00")
 * )
 */
class PuntoEmisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'establecimiento_id' => $this->establecimiento_id,
            'codigo' => $this->codigo,
            'tipo_comprobante' => $this->tipo_comprobante,
            'comprobante' => $this->comprobante,
            'secuencial_actual' => $this->secuencial_actual,
            'estado' => $this->estado,
            'ambiente' => $this->ambiente,
            'identificador_externo' => $this->identificador_externo,
        ];
    }
}
