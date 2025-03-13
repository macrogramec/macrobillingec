<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateEstablecimientoRequest",
 *     title="Update Establecimiento Request",
 *     description="Datos para actualizar un establecimiento",
 *     @OA\Property(
 *         property="direccion",
 *         type="string",
 *         example="Av. Principal 123",
 *         description="Dirección física del establecimiento"
 *     ),
 *     @OA\Property(
 *         property="nombre_comercial",
 *         type="string",
 *         example="Sucursal Principal",
 *         description="Nombre comercial del establecimiento"
 *     ),
 *     @OA\Property(
 *         property="estado",
 *         type="string",
 *         enum={"activo", "inactivo"},
 *         example="activo",
 *         description="Estado del establecimiento"
 *     ),
 *     @OA\Property(
 *         property="ambiente",
 *         type="string",
 *         enum={"produccion", "pruebas"},
 *         example="produccion",
 *         description="Ambiente del establecimiento"
 *     ),
 *     @OA\Property(
 *         property="correos_establecimiento",
 *         type="array",
 *         @OA\Items(type="string", example="sucursal@empresa.com"),
 *         description="Lista de correos electrónicos del establecimiento"
 *     )
 * )
 */
class UpdateEstablecimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direccion' => ['sometimes', 'required', 'string', 'max:300'],
            'nombre_comercial' => ['nullable', 'string', 'max:300'],
            'estado' => ['sometimes', 'required', 'string', 'in:activo,inactivo'],
            'ambiente' => ['sometimes', 'required', 'string', 'in:produccion,pruebas'],
            'correos_establecimiento' => ['nullable', 'array'],
            'correos_establecimiento.*' => ['email', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'direccion.required' => 'La dirección es requerida',
            'estado.in' => 'El estado debe ser activo o inactivo',
            'ambiente.in' => 'El ambiente debe ser produccion o pruebas',
            'correos_establecimiento.*.email' => 'Los correos deben ser válidos'
        ];
    }
}
