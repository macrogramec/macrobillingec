<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Rules\{CamposVersionLiquidacionRule,
    IdentificacionValidaRule,
    ProveedorValidoRule,
    RetencionObligatoriaRule,
    CamposVersionRule};

class LiquidacionCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $identificacion = $this->input('proveedor.identificacion');
        $tipoIdentificacion = $this->input('proveedor.tipo_identificacion');
        $esSociedad = false;

        if ($tipoIdentificacion === '04' && strlen($identificacion) === 13) { // Es RUC
            $tercerDigito = substr($identificacion, 2, 1);
            $esSociedad = in_array($tercerDigito, ['6', '9']); // 6: público, 9: privado
        }

        return [
            // Datos del sistema
            'empresa_id' => 'required|exists:empresas,id',
            'establecimiento_id' => 'required|exists:establecimientos,id',
            'punto_emision_id' => 'required|exists:puntos_emision,id',
            'ambiente' => 'required|in:1,2',
            'tipo_emision' => 'required|in:1',
            'fecha_emision' => 'required|date|before_or_equal:today',
            'version' => 'required|in:1.0.0,1.1.0',  // Agregamos esta validación
            'periodo_fiscal' => [
                'required_if:version,1.1.0',
                'date_format:m/Y'
            ],

            // Datos del proveedor
            'proveedor' => 'required|array',
            'proveedor.tipo_identificacion' => [
                'required',
                'string',
                'in:04,05,06,07,08',
            ],
            'proveedor.identificacion' => [
                'required',
                'string',
                new IdentificacionValidaRule($this->input('proveedor.tipo_identificacion')),
            ],
            'proveedor.razon_social' => 'required|string|max:300',
            'proveedor.email' => 'string|max:300',
            'proveedor.telefono' => 'string|max:300',
            'proveedor.direccion' => 'required|string|max:300',
            'proveedor.tipo' => [
                'required',
                Rule::in([$esSociedad ? 'sociedad' : 'persona_natural'])
            ],
            'proveedor.regimen' => [
                'nullable',
                Rule::requiredIf(fn() => $tipoIdentificacion === '04'), // Requerido solo si es RUC
                Rule::in($esSociedad ? ['general', 'especial'] : ['rimpe', 'general']) // Opciones según tipo
            ],

            // Detalles
            'detalles' => 'required|array|min:1',
            'detalles.*.codigo_principal' => 'required|string|max:25',
            'detalles.*.codigo_auxiliar' => 'nullable|string|max:25',
            'detalles.*.tipo_producto' => [
                'required',
                'string',
                'in:NORMAL,MEDICINAS,CANASTA_BASICA,SERVICIOS_BASICOS,TURISMO,CONSTRUCCION,TRANSPORTE,EXPORTACION'
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
                'exists:tipos_impuestos,codigo_sri,activo,1'
            ],
            'detalles.*.impuestos.*.codigo_porcentaje' => [
                'required',
                'string',
                'exists:tarifas_impuestos,codigo_sri,activo,1'
            ],
            'detalles.*.impuestos.*.tarifa' => [
                'required',
                'numeric',
                'min:0',
                'max:100'
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

            // Retenciones
            'retenciones' => 'nullable|array',
            'retenciones.*.codigo_retencion_id' => [
                'required',
                'exists:codigos_retencion,id,activo,1',
            ],
            'retenciones.*.tipo_impuesto' => 'required|exists:tipos_impuestos,codigo_sri,activo,1',
            'retenciones.*.base_imponible' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'retenciones.*.valor_retenido' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/'
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
            // Información adicional
            'info_adicional' => 'nullable|array',
            'info_adicional.*.nombre' => 'required|string|max:300',
            'info_adicional.*.valor' => 'required|string|max:300',

            // Datos de máquina fiscal (si aplica)
            'maquina_fiscal.marca' => 'nullable|string|max:100',
            'maquina_fiscal.modelo' => 'nullable|string|max:100',
            'maquina_fiscal.serie' => 'nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es requerida',
            'empresa_id.exists' => 'La empresa seleccionada no existe o está inactiva',
            'establecimiento_id.required' => 'El establecimiento es requerido',
            'establecimiento_id.exists' => 'El establecimiento no existe o no pertenece a la empresa',
            'punto_emision_id.required' => 'El punto de emisión es requerido',
            'punto_emision_id.exists' => 'El punto de emisión no existe o no pertenece al establecimiento',

            'ambiente.required' => 'El ambiente es requerido',
            'ambiente.in' => 'El ambiente debe ser 1 (Pruebas) o 2 (Producción)',
            'tipo_emision.required' => 'El tipo de emisión es requerido',
            'tipo_emision.in' => 'El tipo de emisión debe ser 1 (Normal)',
            'fecha_emision.required' => 'La fecha de emisión es requerida',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura',
            'periodo_fiscal.required' => 'El periodo fiscal es requerido',
            'periodo_fiscal.date_format' => 'El periodo fiscal debe tener el formato MM/YYYY',

            'proveedor.tipo_identificacion.required' => 'El tipo de identificación del proveedor es requerido',
            'proveedor.tipo_identificacion.in' => 'El tipo de identificación no es válido',
            'proveedor.identificacion.required' => 'La identificación del proveedor es requerida',
            'proveedor.razon_social.required' => 'La razón social del proveedor es requerida',
            'proveedor.razon_social.max' => 'La razón social no puede exceder los 300 caracteres',
            'proveedor.direccion.required' => 'La dirección del proveedor es requerida',
            'proveedor.direccion.max' => 'La dirección no puede exceder los 300 caracteres',
            'proveedor.tipo.required' => 'El tipo de proveedor es requerido',
            'proveedor.tipo.in' => 'El tipo de proveedor debe ser persona natural o sociedad',
            'proveedor.regimen.required_if' => 'El régimen es requerido para sociedades',
            'proveedor.regimen.in' => 'El régimen debe ser RIMPE o general',

            'detalles.required' => 'La liquidación debe tener al menos un detalle',
            'detalles.*.codigo_principal.required' => 'El código principal es requerido',
            'detalles.*.tipo_producto.required' => 'El tipo de producto es requerido',
            'detalles.*.tipo_producto.in' => 'El tipo de producto seleccionado no es válido',
            'detalles.*.descripcion.required' => 'La descripción es requerida',
            'detalles.*.cantidad.required' => 'La cantidad es requerida',
            'detalles.*.cantidad.regex' => 'La cantidad debe tener máximo 6 decimales',
            'detalles.*.precio_unitario.required' => 'El precio unitario es requerido',
            'detalles.*.precio_unitario.regex' => 'El precio unitario debe tener máximo 6 decimales',

            'detalles.*.impuestos.required' => 'Cada detalle debe tener al menos un impuesto',
            'detalles.*.impuestos.*.codigo.required' => 'El código de impuesto es requerido',
            'detalles.*.impuestos.*.codigo.exists' => 'El código de impuesto no es válido',
            'detalles.*.impuestos.*.codigo_porcentaje.required' => 'El código de porcentaje es requerido',
            'detalles.*.impuestos.*.codigo_porcentaje.exists' => 'El código de porcentaje no es válido',
            'detalles.*.impuestos.*.base_imponible.regex' => 'La base imponible debe tener máximo 2 decimales',
            'detalles.*.impuestos.*.valor.regex' => 'El valor del impuesto debe tener máximo 2 decimales',

            'retenciones.*.codigo.required' => 'El código de retención es requerido',
            'retenciones.*.codigo.exists' => 'El código de retención no es válido',
            'retenciones.*.porcentaje.required' => 'El porcentaje de retención es requerido',
            'retenciones.*.porcentaje.numeric' => 'El porcentaje de retención debe ser un número válido',
            'retenciones.*.valor_retenido.required' => 'El valor retenido es requerido',
            'retenciones.*.valor_retenido.regex' => 'El valor retenido debe tener máximo 2 decimales',

            'info_adicional.*.nombre.required' => 'El nombre del campo adicional es requerido',
            'info_adicional.*.nombre.max' => 'El nombre del campo adicional no puede exceder los 300 caracteres',
            'info_adicional.*.valor.required' => 'El valor del campo adicional es requerido',
            'info_adicional.*.valor.max' => 'El valor del campo adicional no puede exceder los 300 caracteres',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('proveedor.identificacion') &&
            $this->input('proveedor.tipo_identificacion') === '04') {

            $identificacion = $this->input('proveedor.identificacion');
            $tercerDigito = substr($identificacion, 2, 1);
            $esSociedad = in_array($tercerDigito, ['6', '9']);

            // Establecer automáticamente el tipo basado en el RUC
            $this->merge([
                'proveedor' => array_merge($this->input('proveedor'), [
                    'tipo' => $esSociedad ? 'sociedad' : 'persona_natural'
                ])
            ]);
        }
    }
}
