<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateSecuencialRequest",
 *     title="Update Secuencial Request",
 *     description="Datos para actualizar el secuencial de un punto de emisión",
 *     required={"secuencial_actual", "motivo"},
 *     @OA\Property(
 *         property="secuencial_actual",
 *         type="integer",
 *         example=100,
 *         description="Nuevo número secuencial"
 *     ),
 *     @OA\Property(
 *         property="motivo",
 *         type="string",
 *         example="Actualización por cambio de sistema",
 *         description="Motivo del cambio de secuencial"
 *     )
 * )
 */
class UpdateSecuencialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secuencial_actual' => [
                'required',
                'numeric',
                'integer',
                'min:1',
                'regex:/^[0-9]+$/',
                'max:999999999' // Opcional: límite máximo de 9 dígitos
            ],
            'motivo' => [
                'required',
                'string',
                'max:300'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'secuencial_actual.required' => 'El secuencial es requerido',
            'secuencial_actual.numeric' => 'El secuencial debe ser un número',
            'secuencial_actual.integer' => 'El secuencial debe ser un número entero',
            'secuencial_actual.min' => 'El secuencial debe ser mayor a 0',
            'secuencial_actual.regex' => 'El secuencial solo debe contener números',
            'secuencial_actual.max' => 'El secuencial no puede exceder los 9 dígitos',
            'motivo.required' => 'El motivo del cambio es requerido',
            'motivo.string' => 'El motivo debe ser texto',
            'motivo.max' => 'El motivo no puede exceder los 300 caracteres'
        ];
    }
}
