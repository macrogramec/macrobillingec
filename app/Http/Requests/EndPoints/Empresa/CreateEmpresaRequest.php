<?php

namespace App\Http\Requests\EndPoints\Empresa;

use Illuminate\Foundation\Http\FormRequest;


/**
 * @OA\Schema(
 *     schema="CreateEmpresaRequest",
 *     title="Create Empresa Request",
 *     description="Datos requeridos para crear una empresa",
 *     required={"ruc", "razon_social", "direccion_matriz", "obligado_contabilidad", "ambiente", "tipo_emision"},
 *     @OA\Property(property="ruc", type="string", example="0992877878001"),
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
class CreateEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ruc' => ['required', 'string', 'size:13', 'unique:empresas,ruc'],
            'razon_social' => ['required', 'string', 'max:300'],
            'nombre_comercial' => ['nullable', 'string', 'max:300'],
            'direccion_matriz' => ['required', 'string', 'max:300'],
            'obligado_contabilidad' => ['required', 'boolean'],
            'contribuyente_especial' => ['nullable', 'string', 'max:13'],
            'ambiente' => ['required', 'string', 'in:produccion,pruebas'],
            'tipo_emision' => ['required', 'string', 'in:normal,contingencia'],
            'correos_notificacion' => ['string', 'max:100'],
            'regimen_microempresas' => ['required', 'boolean'],
            'agente_retencion' => ['nullable', 'string', 'max:5'],
            'firma_electronica' => ['nullable', 'string', 'max:255'],
            'clave_firma' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'usuario_macrobilling' => ['nullable', 'string', 'max:255'],
            'rimpe' => ['required', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'ruc.required' => 'El RUC es requerido',
            'ruc.size' => 'El RUC debe tener 13 dígitos',
            'ruc.unique' => 'Este RUC ya está registrado',
            'razon_social.required' => 'La razón social es requerida',
            'direccion_matriz.required' => 'La dirección matriz es requerida',
            'ambiente.in' => 'El ambiente debe ser produccion o pruebas',
            'tipo_emision.in' => 'El tipo de emisión debe ser normal o contingencia',
            'correos_notificacion.*.email' => 'Los correos de notificación deben ser válidos'
        ];
    }
}
