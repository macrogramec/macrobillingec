<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => 'required|email|exists:users,email',
            'password' => 'required|string',
            'client_id' => 'required|exists:oauth_clients,id',
            'client_secret' => 'required|string',
            'scope' => 'required|string',
            'environments' => 'sometimes|array',
            'environments.*' => 'string|in:desarrollo,produccion'
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'El correo electrónico es requerido',
            'username.email' => 'Debe ingresar un correo electrónico válido',
            'username.exists' => 'El correo electrónico no está registrado',
            'password.required' => 'La contraseña es requerida',
            'client_id.required' => 'El ID del cliente es requerido',
            'client_id.exists' => 'El cliente no existe',
            'client_secret.required' => 'El secret del cliente es requerido',
            'scope.required' => 'El scope es requerido',
            'environments.array' => 'Los ambientes deben ser un arreglo',
            'environments.*.in' => 'Ambiente inválido'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
                'code' => 'VALIDATION_ERROR'
            ], 422)
        );
    }
}