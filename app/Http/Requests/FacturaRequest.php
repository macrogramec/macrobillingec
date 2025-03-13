<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\{RucValidoRule, IdentificacionValidaRule};

class FacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos principales
            'empresa_id' => 'required|exists:empresas,id',
            'establecimiento_id' => 'required|exists:establecimientos,id',
            'punto_emision_id' => 'required|exists:puntos_emision,id',
            'ambiente' => 'required|integer|min:1|max:2',
            'fechaEmision' => 'required|string|min:1|max:10',
            // Datos del comprador
            'comprador' => 'required|array',
            'comprador.tipo_identificacion' => [
                'required',
                'string',
                'in:04,05,06,07,08',
            ], // 04=RUC, 05=CEDULA, 06=PASAPORTE, 07=CONSUMIDOR_FINAL, 08=IDENTIFICACION_EXTERIOR
            'comprador.identificacion' => [
                'required',
                'string',
                new IdentificacionValidaRule($this->input('comprador.tipo_identificacion')),
            ],
            'comprador.razon_social' => 'required|string|max:300',
            'comprador.direccion' => 'required|string|max:300',
            'comprador.email' => 'required|email|max:300',

            // Detalles de la factura
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo_principal' => 'required|string|max:25',
            'detalles.*.codigo_auxiliar' => 'nullable|string|max:25',
            'detalles.*.tipo_producto' => [
                'required',
                'string',
                'in:NORMAL,MEDICINAS,CANASTA_BASICA,SERVICIOS_BASICOS,SERVICIOS_PROFESIONALES,EDUCACION,REGIMEN_SIMPLIFICADO,ESPECIAL,EXPORTACION'
            ],
            'detalles.*.descripcion' => 'required|string|max:300',
            'detalles.*.cantidad' => [
                'required',
                'numeric',
                'min:0.000001',
                'max:999999999.999999',
                'regex:/^\d+(\.\d{1,6})?$/',
            ],
            'detalles.*.precio_unitario' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.999999',
                'regex:/^\d+(\.\d{1,6})?$/',
            ],
            'detalles.*.descuento' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Impuestos por detalle
            'detalles.*.impuestos' => 'required|array|min:1',
            'detalles.*.impuestos.*.codigo' => [
                'required',
                'string',
                'exists:tipos_impuestos,codigo_sri'
            ],
            'detalles.*.impuestos.*.codigo_porcentaje' => [
                'required',
                'string',
                'exists:tarifas_impuestos,codigo_sri'
            ],
            'detalles.*.impuestos.*.impuesto_tarifa' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'detalles.*.impuestos.*.base_imponible' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'detalles.*.impuestos.*.valor' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Formas de pago
            'formas_pago' => 'required|array|min:1',
            'formas_pago.*.forma_pago' => [
                'required',
                'string',
                'exists:formas_pago,codigo'
            ],
            'formas_pago.*.total' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'formas_pago.*.plazo' => 'nullable|integer|min:0',
            'formas_pago.*.unidad_tiempo' => [
                'nullable',
                'required_with:formas_pago.*.plazo',
                'in:dias,meses,ninguno'
            ],

            // Detalles adicionales (ahora requerido)
            'detalles_adicionales' => 'required|array|min:1',
            'detalles_adicionales.*.nombre' => 'required|string|max:300',
            'detalles_adicionales.*.valor' => 'required|string|max:300',

            // Datos adicionales opcionales
            'propina' => 'nullable|numeric|min:0|max:999999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'observacion' => 'nullable|string|max:300',
        ];
    }



    public function messages(): array
    {
        return [
            'empresa_id.exists' => 'La empresa seleccionada no existe o no está activa',
            'establecimiento_id.exists' => 'El establecimiento no existe o no pertenece a la empresa',
            'punto_emision_id.exists' => 'El punto de emisión no existe o no pertenece al establecimiento',
            'comprador.tipo_identificacion.in' => 'El tipo de identificación del comprador no es válido',
            'detalles.required' => 'La factura debe tener al menos un detalle',
            'detalles.*.cantidad.regex' => 'La cantidad debe tener máximo 6 decimales',
            'detalles.*.precio_unitario.regex' => 'El precio unitario debe tener máximo 6 decimales',
            'detalles.*.descuento.regex' => 'El descuento debe tener máximo 2 decimales',
            'detalles.*.impuestos.*.valor.regex' => 'El valor del impuesto debe tener máximo 2 decimales',
            'formas_pago.required' => 'Debe especificar al menos una forma de pago',
            'formas_pago.*.unidad_tiempo.in' => 'La unidad de tiempo debe ser días o meses',
            'detalles_adicionales.required' => 'Debe incluir al menos un detalle adicional',
            'detalles_adicionales.*.nombre.required' => 'El nombre del detalle adicional es requerido',
            'detalles_adicionales.*.valor.required' => 'El valor del detalle adicional es requerido',
        ];
    }

    public function attributes(): array
    {
        return [
            'empresa_id' => 'empresa',
            'establecimiento_id' => 'establecimiento',
            'punto_emision_id' => 'punto de emisión',
            'comprador.tipo_identificacion' => 'tipo de identificación del comprador',
            'comprador.identificacion' => 'identificación del comprador',
            'comprador.razon_social' => 'razón social del comprador',
            'comprador.direccion' => 'dirección del comprador',
            'comprador.email' => 'email del comprador',
            'detalles.*.codigo_principal' => 'código principal',
            'detalles.*.descripcion' => 'descripción',
            'detalles.*.cantidad' => 'cantidad',
            'detalles.*.precio_unitario' => 'precio unitario',
            'detalles.*.descuento' => 'descuento',
            'formas_pago.*.forma_pago' => 'forma de pago',
            'formas_pago.*.total' => 'total',
            'formas_pago.*.plazo' => 'plazo',
            'formas_pago.*.unidad_tiempo' => 'unidad de tiempo',
            'detalles_adicionales.*.nombre' => 'nombre del detalle adicional',
            'detalles_adicionales.*.valor' => 'valor del detalle adicional',
        ];
    }
}
