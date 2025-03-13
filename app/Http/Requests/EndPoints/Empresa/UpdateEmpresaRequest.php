<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;
/**
 * @OA\Schema(
 *     schema="UpdateEmpresaRequest",
 *     title="Update Empresa Request",
 *     description="Datos para actualizar una empresa",
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
 *     @OA\Property(property="rimpe", type="boolean", example=false)
 * )
 */
class UpdateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social' => ['sometimes', 'required', 'string', 'max:300'],
            'nombre_comercial' => ['nullable', 'string', 'max:300'],
            'direccion_matriz' => ['sometimes', 'required', 'string', 'max:300'],
            'obligado_contabilidad' => ['sometimes', 'required', 'boolean'],
            'contribuyente_especial' => ['nullable', 'string', 'max:13'],
            'ambiente' => ['sometimes', 'required', 'string', 'in:produccion,pruebas'],
            'tipo_emision' => ['sometimes', 'required', 'string', 'in:normal,contingencia'],
            'correos_notificacion' => ['nullable', 'array'],
            'correos_notificacion.*' => ['email', 'max:100'],
            'regimen_microempresas' => ['sometimes', 'required', 'boolean'],
            'agente_retencion' => ['nullable', 'string', 'max:5'],
            'rimpe' => ['sometimes', 'required', 'boolean']
        ];
    }
}
