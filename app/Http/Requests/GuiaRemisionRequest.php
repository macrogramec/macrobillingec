<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\{IdentificacionValidaRule};

class GuiaRemisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos básicos
            'empresa_id' => 'required|exists:empresas,id',
            'establecimiento_id' => 'required|exists:establecimientos,id',
            'punto_emision_id' => 'required|exists:puntos_emision,id',
            'ambiente' => 'required|in:1,2',
            'tipo_emision' => 'required|in:1',
            'fecha_ini_transporte' => 'required|date|before_or_equal:today',
            'fecha_fin_transporte' => 'required|date|after_or_equal:fecha_ini_transporte',

            // Datos del transportista
            'transportista' => 'required|array',
            'transportista.tipo_identificacion' => 'required|in:04,05,06,07,08',
            'transportista.identificacion' => [
                'required',
                'string',
                new IdentificacionValidaRule($this->input('transportista.tipo_identificacion')),
            ],
            'transportista.razon_social' => 'required|string|max:300',
            'transportista.placa' => 'required|string|max:20',

            // Dirección de partida
            'dir_partida' => 'required|string|max:300',

            // Destinatarios
            'destinatarios' => 'required|array|min:1',
            'destinatarios.*.identificacion' => 'required|string|max:20',
            'destinatarios.*.razon_social' => 'required|string|max:300',
            'destinatarios.*.email' => 'string|max:300',
            'destinatarios.*.direccion' => 'required|string|max:300',
            'destinatarios.*.motivo_traslado' => 'required|string|max:300',
            'destinatarios.*.doc_aduanero' => 'nullable|string|max:20',
            'destinatarios.*.cod_establecimiento_destino' => 'nullable|string|max:3',
            'destinatarios.*.ruta' => 'nullable|string|max:300',
            'destinatarios.*.doc_sustento' => 'nullable|array',
            'destinatarios.*.doc_sustento.tipo' => 'nullable|in:01,03,04,05,06,07',
            'destinatarios.*.doc_sustento.numero' => 'nullable|regex:/^[0-9]{3}-[0-9]{3}-[0-9]{9}$/',
            'destinatarios.*.doc_sustento.autorizacion' => 'nullable|string|max:49',
            'destinatarios.*.doc_sustento.fecha_emision' => 'nullable|date',

            // Detalles de cada destinatario
            'destinatarios.*.detalles' => 'required|array|min:1',
            'destinatarios.*.detalles.*.codigo_interno' => 'nullable|string|max:25',
            'destinatarios.*.detalles.*.codigo_adicional' => 'nullable|string|max:25',
            'destinatarios.*.detalles.*.descripcion' => 'required|string|max:300',
            'destinatarios.*.detalles.*.cantidad' => [
                'required',
                'numeric',
                'min:0.000001',
                'max:999999999.999999',
                'regex:/^\d+(\.\d{1,6})?$/'
            ],

            // Detalles adicionales por ítem
            'destinatarios.*.detalles.*.detalles_adicionales' => 'nullable|array',
            'destinatarios.*.detalles.*.detalles_adicionales.*.nombre' => 'required|string|max:300',
            'destinatarios.*.detalles.*.detalles_adicionales.*.valor' => 'required|string|max:300',

            // Información adicional general
            'info_adicional' => 'nullable|array',
            'info_adicional.*.nombre' => 'required|string|max:300',
            'info_adicional.*.valor' => 'required|string|max:300',
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.exists' => 'La empresa seleccionada no existe o no está activa',
            'establecimiento_id.exists' => 'El establecimiento no existe o no pertenece a la empresa',
            'punto_emision_id.exists' => 'El punto de emisión no existe o no pertenece al establecimiento',
            'ambiente.in' => 'El ambiente debe ser 1 (Pruebas) o 2 (Producción)',
            'tipo_emision.in' => 'El tipo de emisión debe ser 1 (Normal)',
            'fecha_ini_transporte.required' => 'La fecha de inicio de transporte es requerida',
            'fecha_ini_transporte.before_or_equal' => 'La fecha de inicio de transporte no puede ser futura',
            'fecha_fin_transporte.required' => 'La fecha de fin de transporte es requerida',
            'fecha_fin_transporte.after_or_equal' => 'La fecha de fin de transporte debe ser igual o posterior a la fecha de inicio',

            'transportista.required' => 'Los datos del transportista son requeridos',
            'transportista.tipo_identificacion.required' => 'El tipo de identificación del transportista es requerido',
            'transportista.identificacion.required' => 'La identificación del transportista es requerida',
            'transportista.razon_social.required' => 'La razón social del transportista es requerida',
            'transportista.placa.required' => 'La placa del vehículo es requerida',

            'dir_partida.required' => 'La dirección de partida es requerida',

            'destinatarios.required' => 'Debe especificar al menos un destinatario',
            'destinatarios.min' => 'Debe especificar al menos un destinatario',
            'destinatarios.*.identificacion.required' => 'La identificación del destinatario es requerida',
            'destinatarios.*.razon_social.required' => 'La razón social del destinatario es requerida',
            'destinatarios.*.direccion.required' => 'La dirección del destinatario es requerida',
            'destinatarios.*.motivo_traslado.required' => 'El motivo de traslado es requerido',

            'destinatarios.*.detalles.required' => 'Debe especificar al menos un detalle por destinatario',
            'destinatarios.*.detalles.min' => 'Debe especificar al menos un detalle por destinatario',
            'destinatarios.*.detalles.*.descripcion.required' => 'La descripción del producto es requerida',
            'destinatarios.*.detalles.*.cantidad.required' => 'La cantidad es requerida',
            'destinatarios.*.detalles.*.cantidad.numeric' => 'La cantidad debe ser un número válido',
            'destinatarios.*.detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'destinatarios.*.detalles.*.cantidad.regex' => 'La cantidad debe tener máximo 6 decimales',

            'destinatarios.*.doc_sustento.tipo.in' => 'El tipo de documento de sustento no es válido',
            'destinatarios.*.doc_sustento.numero.regex' => 'El número de documento debe tener el formato 001-001-000000001'
        ];
    }

    public function attributes(): array
    {
        return [
            'empresa_id' => 'empresa',
            'establecimiento_id' => 'establecimiento',
            'punto_emision_id' => 'punto de emisión',
            'ambiente' => 'ambiente',
            'tipo_emision' => 'tipo de emisión',
            'fecha_ini_transporte' => 'fecha de inicio de transporte',
            'fecha_fin_transporte' => 'fecha de fin de transporte',
            'transportista.tipo_identificacion' => 'tipo de identificación del transportista',
            'transportista.identificacion' => 'identificación del transportista',
            'transportista.razon_social' => 'razón social del transportista',
            'transportista.rise' => 'RISE',
            'transportista.placa' => 'placa del vehículo',
            'dir_partida' => 'dirección de partida',
            'destinatarios.*.identificacion' => 'identificación del destinatario',
            'destinatarios.*.razon_social' => 'razón social del destinatario',
            'destinatarios.*.direccion' => 'dirección del destinatario',
            'destinatarios.*.motivo_traslado' => 'motivo de traslado',
            'destinatarios.*.doc_aduanero' => 'documento aduanero',
            'destinatarios.*.cod_establecimiento_destino' => 'código de establecimiento destino',
            'destinatarios.*.ruta' => 'ruta',
            'destinatarios.*.doc_sustento.tipo' => 'tipo de documento sustento',
            'destinatarios.*.doc_sustento.numero' => 'número de documento sustento',
            'destinatarios.*.doc_sustento.autorizacion' => 'número de autorización',
            'destinatarios.*.doc_sustento.fecha_emision' => 'fecha de emisión del documento sustento',
            'destinatarios.*.detalles.*.codigo_interno' => 'código interno',
            'destinatarios.*.detalles.*.codigo_adicional' => 'código adicional',
            'destinatarios.*.detalles.*.descripcion' => 'descripción',
            'destinatarios.*.detalles.*.cantidad' => 'cantidad',
            'destinatarios.*.detalles.*.detalles_adicionales.*.nombre' => 'nombre del detalle adicional',
            'destinatarios.*.detalles.*.detalles_adicionales.*.valor' => 'valor del detalle adicional',
            'info_adicional.*.nombre' => 'nombre del campo adicional',
            'info_adicional.*.valor' => 'valor del campo adicional'
        ];
    }
}
