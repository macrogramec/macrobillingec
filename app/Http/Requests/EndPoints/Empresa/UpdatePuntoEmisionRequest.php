<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdatePuntoEmisionRequest",
 *     title="Update Punto de Emisión Request",
 *     description="Datos para actualizar un punto de emisión",
 *     @OA\Property(
 *         property="estado",
 *         type="string",
 *         enum={"activo", "inactivo"},
 *         example="activo"
 *     ),
 *     @OA\Property(
 *         property="ambiente",
 *         type="string",
 *         enum={"produccion", "pruebas"},
 *         example="produccion"
 *     ),
 *     @OA\Property(property="identificador_externo", type="string", example="POS-001")
 * )
 */
class UpdatePuntoEmisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['sometimes', 'required', 'string', 'in:activo,inactivo'],
            'ambiente' => ['sometimes', 'required', 'string', 'in:produccion,pruebas'],
            'identificador_externo' => ['nullable', 'string', 'max:100']
        ];
    }
}
