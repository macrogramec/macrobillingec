<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularRetencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => 'required|string|min:10|max:300',
            'usuario' => 'required|string|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de anulación es requerido',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivo.max' => 'El motivo no puede exceder los 300 caracteres',
            'usuario.required' => 'El usuario que realiza la anulación es requerido'
        ];
    }
}
