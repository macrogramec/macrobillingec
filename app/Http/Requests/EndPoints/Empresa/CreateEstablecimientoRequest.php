<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CreateEstablecimientoRequest",
 *     title="Create Establecimiento Request",
 *     description="Datos requeridos para crear un establecimiento",
 *     required={"codigo", "direccion", "estado", "ambiente"},
 *     @OA\Property(
 *         property="codigo",
 *         type="string",
 *         example="001",
 *         description="Código de 3 dígitos del establecimiento"
 *     ),
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
class CreateEstablecimientoRequest extends FormRequest
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
                'regex:/^[0-9]{3}$/',
                'unique:establecimientos,codigo,NULL,id,empresa_id,' . $this->route('empresa')->id
            ],
            'direccion' => ['required', 'string', 'max:300'],
            'nombre_comercial' => ['nullable', 'string', 'max:300'],
            'estado' => ['required', 'string', 'in:activo,inactivo'],
            'ambiente' => ['required', 'string', 'in:produccion,pruebas'],
            'uuid' => ['nullable', 'string', 'uuid'], // Se puede omitir si no se envía
            'correos_establecimiento' => ['nullable', 'array'],
            'correos_establecimiento.*' => ['email', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es requerido',
            'codigo.size' => 'El código debe tener exactamente 3 dígitos',
            'codigo.regex' => 'El código debe contener solo números',
            'codigo.unique' => 'Este código ya está en uso para esta empresa',
            'direccion.required' => 'La dirección es requerida',
            'estado.in' => 'El estado debe ser activo o inactivo',
            'ambiente.in' => 'El ambiente debe ser produccion o pruebas',
            'correos_establecimiento.*.email' => 'Los correos deben ser válidos'
        ];
    }
}
