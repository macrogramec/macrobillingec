<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\{IdentificacionValidaRule, RetencionActivaRule};

class RetencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // Datos básicos
            'empresa_id' => 'required|exists:empresas,id',
            'establecimiento_id' => 'required|exists:establecimientos,id',
            'punto_emision_id' => 'required|exists:puntos_emision,id',
            'ambiente' => 'required|in:1,2',
            'tipo_emision' => 'required|in:1',
            'fecha_emision' => 'required|date|before_or_equal:today',
            'periodo_fiscal' => ['required', 'regex:/^(0[1-9]|1[0-2])\/20[0-9]{2}$/'],

            // Datos del sujeto retenido
            'sujeto' => 'required|array',
            'sujeto.tipo_identificacion' => 'required|in:04,05,06,07,08',
            'sujeto.identificacion' => [
                'required',
                'string',
                new IdentificacionValidaRule($this->input('sujeto.tipo_identificacion'))
            ],
            'sujeto.razon_social' => 'required|string|max:300',
            'sujeto.direccion' => 'required|string|max:300',
            'sujeto.email' => 'required|email|max:300',
            'sujeto.tipo_sujeto' => 'required|in:sociedad,persona_natural',
            'sujeto.regimen' => 'required_if:sujeto.tipo_sujeto,sociedad|in:rimpe,general',

            // Detalles de retención
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo_retencion_id' => [
                //'required',
                'exists:codigos_retencion,id,activo,1',
                new RetencionActivaRule()
            ],
            'detalles.*.tipo_impuesto' => 'required|string|max:300',
            'detalles.*.codigo' => 'required|string|max:300',
            'detalles.*.base_imponible' => 'required|string|max:300',
            'detalles.*.doc_sustento' => 'required|array',
            'detalles.*.doc_sustento.codigo' => 'required|in:01,02,03,04,05,06,07',
            'detalles.*.doc_sustento.numero' => [
                'required',
                'regex:/^[0-9]{3}-[0-9]{3}-[0-9]{9}$/'
            ],
            'detalles.*.doc_sustento.fecha_emision' => 'required|date|before_or_equal:today',

            // Información adicional
            'info_adicional' => 'nullable|array',
            'info_adicional.*.nombre' => 'required|string|max:300',
            'info_adicional.*.valor' => 'required|string|max:300',
        ];

        // Reglas adicionales para retenciones de dividendos
        if ($this->input('tipo_retencion') === 'dividendos') {
            $rules = array_merge($rules, [
                'ejercicio_fiscal' => 'required|digits:4|date_format:Y',
                'fecha_pago' => 'required|date|before_or_equal:today',
                'valor_pago' => 'required|numeric|min:0|max:999999999.99',

                // Beneficiarios
                'beneficiarios' => 'required|array|min:1',
                'beneficiarios.*.tipo' => 'required|in:04,05,06,07,08',
                'beneficiarios.*.identificacion' => [
                    'required',
                    'string',
                    new IdentificacionValidaRule($this->input('beneficiarios.*.tipo'))
                ],
                'beneficiarios.*.razon_social' => 'required|string|max:300',
                'beneficiarios.*.tipo_cuenta' => 'required|in:AHORRO,CORRIENTE',
                'beneficiarios.*.numero_cuenta' => 'required|string|max:20',
                'beneficiarios.*.banco' => 'required|exists:catalogos_bancos,codigo',
                'beneficiarios.*.porcentaje_participacion' => 'required|numeric|min:0|max:100',

                // Valores específicos para dividendos
                'detalles.*.utilidad_antes_ir' => 'required|numeric|min:0',
                'detalles.*.impuesto_renta_sociedad' => 'required|numeric|min:0',
                'detalles.*.utilidad_efectiva' => 'required|numeric|min:0'
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'periodo_fiscal.regex' => 'El período fiscal debe tener el formato MM/YYYY',
            'sujeto.tipo_identificacion.in' => 'El tipo de identificación no es válido',
            'sujeto.regimen.required_if' => 'El régimen es requerido para sociedades',
            'detalles.*.doc_sustento.numero.regex' => 'El número de documento debe tener el formato 001-001-000000001',
            'ejercicio_fiscal.date_format' => 'El ejercicio fiscal debe ser un año válido',
            'beneficiarios.*.tipo_cuenta.in' => 'El tipo de cuenta debe ser AHORRO o CORRIENTE',
            'beneficiarios.*.porcentaje_participacion.max' => 'El porcentaje de participación no puede ser mayor a 100'
        ];
    }
}
