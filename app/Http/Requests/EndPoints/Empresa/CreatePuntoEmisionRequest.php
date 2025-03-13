<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CreatePuntoEmisionRequest",
 *     title="Create Punto de Emisión Request",
 *     description="Datos requeridos para crear un punto de emisión",
 *     required={"codigo", "tipo_comprobante", "secuencial_actual", "estado", "ambiente"},
 *     @OA\Property(property="codigo", type="string", example="001"),
 *     @OA\Property(
 *         property="tipo_comprobante",
 *         type="string",
 *         enum={"01", "02", "03", "04", "05", "06", "07"},
 *         example="01"
 *     ),
 *     @OA\Property(property="secuencial_actual", type="integer", example=1),
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
class CreatePuntoEmisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'size:3',
                'regex:/^[0-9]{3}$/'
            ],
            'tipo_comprobante' => ['required', 'string', 'in:01,02,03,04,05,06,07'],
            'secuencial_actual' => ['required', 'integer', 'min:1'],
            'comprobante' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'string', 'in:activo,inactivo'],
            'ambiente' => ['required', 'string', 'in:produccion,pruebas'],
            'identificador_externo' => ['nullable', 'string', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es requerido',
            'codigo.size' => 'El código debe tener exactamente 3 dígitos',
            'codigo.regex' => 'El código debe contener solo números',
            'codigo.unique' => 'Este código ya está en uso para este establecimiento',
            'tipo_comprobante.required' => 'El tipo de comprobante es requerido',
            'tipo_comprobante.in' => 'El tipo de comprobante no es válido',
            'secuencial_actual.required' => 'El secuencial actual es requerido',
            'secuencial_actual.min' => 'El secuencial actual debe ser mayor a 0'
        ];
    }
}
