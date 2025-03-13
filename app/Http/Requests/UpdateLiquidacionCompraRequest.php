<?php

namespace App\Http\Requests;

class UpdateLiquidacionCompraRequest extends LiquidacionCompraRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Validaciones adicionales para actualización
        $rules['id'] = 'required|exists:liquidaciones_compra,id';

        // Validar que no esté autorizada
        $rules['estado'] = [
            'required',
            'string',
            function ($attribute, $value, $fail) {
                $liquidacion = $this->route('liquidacion');
                if ($liquidacion && $liquidacion->estaAutorizada()) {
                    $fail('No se puede modificar una liquidación autorizada.');
                }
            }
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'id.required' => 'El ID de la liquidación es requerido.',
            'id.exists' => 'La liquidación seleccionada no existe.',
            'estado.required' => 'El estado es requerido.',
        ]);
    }
}
