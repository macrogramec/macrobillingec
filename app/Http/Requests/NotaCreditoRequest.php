<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\{RucValidoRule, IdentificacionValidaRule, ClaveAccesoValidaRule};

/**
 * @OA\Schema(
 *     schema="NotaCreditoExternaRequest",
 *     required={"ambiente", "tipo_emision", "fecha_emision", "comprador", "doc_modificado", "detalles"},
 *     @OA\Property(property="ambiente", type="string", enum={"1", "2"}, example="1", description="1: Pruebas, 2: Producción"),
 *     @OA\Property(property="tipo_emision", type="string", enum={"1"}, example="1", description="1: Normal"),
 *     @OA\Property(property="fecha_emision", type="string", format="date", example="2024-12-23"),
 *     @OA\Property(
 *         property="comprador",
 *         type="object",
 *         required={"tipo_identificacion", "identificacion", "razon_social", "direccion", "email"},
 *         @OA\Property(property="tipo_identificacion", type="string", enum={"04", "05", "06", "07", "08"}, example="04"),
 *         @OA\Property(property="identificacion", type="string", example="0992877878001"),
 *         @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
 *         @OA\Property(property="direccion", type="string", example="Guayaquil - Ecuador"),
 *         @OA\Property(property="email", type="string", format="email", example="ejemplo@mail.com")
 *     ),
 *     @OA\Property(
 *         property="doc_modificado",
 *         type="object",
 *         required={"tipo_doc", "fecha_emision", "numero", "motivo"},
 *         @OA\Property(property="tipo_doc", type="string", enum={"01"}, example="01"),
 *         @OA\Property(property="fecha_emision", type="string", format="date", example="2024-12-20"),
 *         @OA\Property(property="numero", type="string", example="001-001-000000001"),
 *         @OA\Property(property="motivo", type="string", example="Devolución de mercadería")
 *     ),
 *     @OA\Property(
 *         property="detalles",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"codigo_principal", "descripcion", "cantidad", "precio_unitario", "impuestos"},
 *             @OA\Property(property="codigo_principal", type="string", example="001"),
 *             @OA\Property(property="codigo_auxiliar", type="string", example="AUX001"),
 *             @OA\Property(property="descripcion", type="string", example="Producto de prueba"),
 *             @OA\Property(property="cantidad", type="number", format="float", example=1),
 *             @OA\Property(property="precio_unitario", type="number", format="float", example=100),
 *             @OA\Property(property="descuento", type="number", format="float", example=0),
 *             @OA\Property(
 *                 property="impuestos",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"codigo", "codigo_porcentaje", "base_imponible", "valor"},
 *                     @OA\Property(property="codigo", type="string", example="2"),
 *                     @OA\Property(property="codigo_porcentaje", type="string", example="2"),
 *                     @OA\Property(property="base_imponible", type="number", example=100),
 *                     @OA\Property(property="valor", type="number", example=15)
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NotaCreditoInternaRequest",
 *     required={"factura_id", "tipo_aplicacion", "detalles"},
 *     @OA\Property(property="factura_id", type="integer", example=1),
 *     @OA\Property(property="tipo_aplicacion", type="string", enum={"TOTAL", "PARCIAL"}, example="PARCIAL"),
 *     @OA\Property(property="motivo_general", type="string", example="Devolución parcial de mercadería"),
 *     @OA\Property(
 *         property="detalles",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             required={"factura_detalle_id", "cantidad_devuelta", "motivo"},
 *             @OA\Property(property="factura_detalle_id", type="integer", example=1),
 *             @OA\Property(property="cantidad_devuelta", type="number", format="float", example=1),
 *             @OA\Property(property="motivo", type="string", example="Producto en mal estado")
 *         )
 *     )
 * )
 */
class NotaCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Datos de referencia del sistema
            'empresa_id' => 'required|exists:empresas,id',
            'establecimiento_id' => 'required|exists:establecimientos,id',
            'punto_emision_id' => 'required|exists:puntos_emision,id',

            // Datos de la factura original (opcional si es NC manual)
            'factura_id' => 'nullable|exists:facturas,id',

            // Datos principales según SRI
            'ambiente' => 'required|string|in:1,2', // 1: Pruebas, 2: Producción
            'tipo_emision' => 'required|string|in:1,2', // 1: Normal, 2: Contingencia
            'fecha_emision' => 'required|date|before_or_equal:today',
            'periodo_fiscal' => 'required|date_format:m/Y',

            // Datos del comprador
            'comprador' => 'required|array',
            'comprador.tipo_identificacion' => [
                'required',
                'string',
                'in:04,05,06,07,08', // 04:RUC, 05:Cédula, 06:Pasaporte, 07:Consumidor Final, 08:Id Exterior
            ],
            'comprador.identificacion' => [
                'required',
                'string',
                new IdentificacionValidaRule($this->input('comprador.tipo_identificacion')),
            ],
            'comprador.razon_social' => 'required|string|max:300',
            'comprador.direccion' => 'required|string|max:300',
            'comprador.email' => 'required|email|max:300',

            // Datos del documento modificado (factura)
            'doc_modificado' => 'required|array',
            'doc_modificado.tipo_doc' => 'required|string|in:01', // 01: Factura
            'doc_modificado.fecha_emision' => 'required|date',
            'doc_modificado.numero' => [
                'required',
                'string',
                'regex:/^[0-9]{3}-[0-9]{3}-[0-9]{9}$/', // formato: 001-001-000000001
            ],
            'doc_modificado.motivo' => 'required|string|max:300',

            // Detalles de la nota de crédito
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
                'exists:tipos_impuestos,codigo_sri'
            ],
            'detalles.*.impuestos.*.codigo_porcentaje' => [
                'required',
                'string',
                'exists:tarifas_impuestos,codigo_sri'
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

            // Información adicional
            'info_adicional' => 'nullable|array',
            'info_adicional.*.nombre' => 'required|string|max:300',
            'info_adicional.*.valor' => 'required|string|max:300',

            // Datos de retención si aplica
            'retenciones' => 'nullable|array',
            'retenciones.*.codigo' => 'required|string|exists:tipos_retenciones,codigo',
            'retenciones.*.porcentaje' => 'required|numeric|min:0|max:100',
            'retenciones.*.valor' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ];
    }
    /**
     * Mensajes de error personalizados para las validaciones
     */
    public function messages(): array
    {
        return [
            // Mensajes para datos del sistema
            'empresa_id.required' => 'La empresa es requerida',
            'empresa_id.exists' => 'La empresa seleccionada no existe o está inactiva',
            'establecimiento_id.required' => 'El establecimiento es requerido',
            'establecimiento_id.exists' => 'El establecimiento no existe o no pertenece a la empresa',
            'punto_emision_id.required' => 'El punto de emisión es requerido',
            'punto_emision_id.exists' => 'El punto de emisión no existe o no pertenece al establecimiento',

            // Mensajes para datos principales
            'ambiente.required' => 'El ambiente es requerido',
            'ambiente.in' => 'El ambiente debe ser 1 (Pruebas) o 2 (Producción)',
            'tipo_emision.required' => 'El tipo de emisión es requerido',
            'tipo_emision.in' => 'El tipo de emisión debe ser 1 (Normal) o 2 (Contingencia)',
            'fecha_emision.required' => 'La fecha de emisión es requerida',
            'fecha_emision.before_or_equal' => 'La fecha de emisión no puede ser futura',
            'periodo_fiscal.required' => 'El periodo fiscal es requerido',
            'periodo_fiscal.date_format' => 'El periodo fiscal debe tener el formato MM/YYYY',

            // Mensajes para datos del comprador
            'comprador.required' => 'Los datos del comprador son requeridos',
            'comprador.tipo_identificacion.required' => 'El tipo de identificación del comprador es requerido',
            'comprador.tipo_identificacion.in' => 'El tipo de identificación no es válido',
            'comprador.identificacion.required' => 'La identificación del comprador es requerida',
            'comprador.razon_social.required' => 'La razón social del comprador es requerida',
            'comprador.razon_social.max' => 'La razón social no puede exceder los 300 caracteres',
            'comprador.direccion.required' => 'La dirección del comprador es requerida',
            'comprador.direccion.max' => 'La dirección no puede exceder los 300 caracteres',
            'comprador.email.required' => 'El email del comprador es requerido',
            'comprador.email.email' => 'El email debe ser una dirección válida',

            // Mensajes para documento modificado
            'doc_modificado.required' => 'Los datos del documento modificado son requeridos',
            'doc_modificado.tipo_doc.required' => 'El tipo de documento modificado es requerido',
            'doc_modificado.tipo_doc.in' => 'El tipo de documento debe ser 01 (Factura)',
            'doc_modificado.fecha_emision.required' => 'La fecha de emisión del documento modificado es requerida',
            'doc_modificado.fecha_emision.before' => 'La fecha del documento modificado debe ser anterior a la nota de crédito',
            'doc_modificado.numero.required' => 'El número del documento modificado es requerido',
            'doc_modificado.numero.regex' => 'El número del documento debe tener el formato 001-001-000000001',
            'doc_modificado.motivo.required' => 'El motivo de modificación es requerido',
            'doc_modificado.motivo.max' => 'El motivo no puede exceder los 300 caracteres',

            // Mensajes para detalles
            'detalles.required' => 'La nota de crédito debe tener al menos un detalle',
            'detalles.min' => 'La nota de crédito debe tener al menos un detalle',
            'detalles.*.codigo_principal.required' => 'El código principal es requerido',
            'detalles.*.codigo_principal.max' => 'El código principal no puede exceder los 25 caracteres',
            'detalles.*.tipo_producto.required' => 'El tipo de producto es requerido',
            'detalles.*.tipo_producto.in' => 'El tipo de producto seleccionado no es válido',
            'detalles.*.descripcion.required' => 'La descripción es requerida',
            'detalles.*.descripcion.max' => 'La descripción no puede exceder los 300 caracteres',
            'detalles.*.cantidad.required' => 'La cantidad es requerida',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número válido',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0',
            'detalles.*.cantidad.regex' => 'La cantidad debe tener máximo 6 decimales',
            'detalles.*.precio_unitario.required' => 'El precio unitario es requerido',
            'detalles.*.precio_unitario.numeric' => 'El precio unitario debe ser un número válido',
            'detalles.*.precio_unitario.regex' => 'El precio unitario debe tener máximo 6 decimales',
            'detalles.*.descuento.numeric' => 'El descuento debe ser un número válido',
            'detalles.*.descuento.regex' => 'El descuento debe tener máximo 2 decimales',

            // Mensajes para impuestos
            'detalles.*.impuestos.required' => 'Cada detalle debe tener al menos un impuesto',
            'detalles.*.impuestos.*.codigo.required' => 'El código de impuesto es requerido',
            'detalles.*.impuestos.*.codigo.exists' => 'El código de impuesto no es válido',
            'detalles.*.impuestos.*.codigo_porcentaje.required' => 'El código de porcentaje es requerido',
            'detalles.*.impuestos.*.codigo_porcentaje.exists' => 'El código de porcentaje no es válido',
            'detalles.*.impuestos.*.tarifa.required' => 'La tarifa es requerida',
            'detalles.*.impuestos.*.tarifa.numeric' => 'La tarifa debe ser un número válido',
            'detalles.*.impuestos.*.base_imponible.required' => 'La base imponible es requerida',
            'detalles.*.impuestos.*.base_imponible.regex' => 'La base imponible debe tener máximo 2 decimales',
            'detalles.*.impuestos.*.valor.required' => 'El valor del impuesto es requerido',
            'detalles.*.impuestos.*.valor.regex' => 'El valor del impuesto debe tener máximo 2 decimales',

            // Mensajes para información adicional
            'info_adicional.*.nombre.required' => 'El nombre del campo adicional es requerido',
            'info_adicional.*.nombre.max' => 'El nombre del campo adicional no puede exceder los 300 caracteres',
            'info_adicional.*.valor.required' => 'El valor del campo adicional es requerido',
            'info_adicional.*.valor.max' => 'El valor del campo adicional no puede exceder los 300 caracteres',

            // Mensajes para retenciones
            'retenciones.*.codigo.required' => 'El código de retención es requerido',
            'retenciones.*.codigo.exists' => 'El código de retención no es válido',
            'retenciones.*.porcentaje.required' => 'El porcentaje de retención es requerido',
            'retenciones.*.porcentaje.numeric' => 'El porcentaje de retención debe ser un número válido',
            'retenciones.*.valor.required' => 'El valor de retención es requerido',
            'retenciones.*.valor.regex' => 'El valor de retención debe tener máximo 2 decimales',
        ];
    }

    /**
     * Nombres personalizados para los atributos
     */
    public function attributes(): array
    {
        return [
            'empresa_id' => 'empresa',
            'establecimiento_id' => 'establecimiento',
            'punto_emision_id' => 'punto de emisión',
            'ambiente' => 'ambiente',
            'tipo_emision' => 'tipo de emisión',
            'fecha_emision' => 'fecha de emisión',
            'periodo_fiscal' => 'periodo fiscal',
            'comprador.tipo_identificacion' => 'tipo de identificación del comprador',
            'comprador.identificacion' => 'identificación del comprador',
            'comprador.razon_social' => 'razón social del comprador',
            'comprador.direccion' => 'dirección del comprador',
            'comprador.email' => 'email del comprador',
            'doc_modificado.tipo_doc' => 'tipo de documento modificado',
            'doc_modificado.fecha_emision' => 'fecha de emisión del documento',
            'doc_modificado.numero' => 'número del documento',
            'doc_modificado.motivo' => 'motivo de modificación',
            'detalles.*.codigo_principal' => 'código principal',
            'detalles.*.codigo_auxiliar' => 'código auxiliar',
            'detalles.*.tipo_producto' => 'tipo de producto',
            'detalles.*.descripcion' => 'descripción',
            'detalles.*.cantidad' => 'cantidad',
            'detalles.*.precio_unitario' => 'precio unitario',
            'detalles.*.descuento' => 'descuento',
            'detalles.*.impuestos.*.codigo' => 'código de impuesto',
            'detalles.*.impuestos.*.codigo_porcentaje' => 'código de porcentaje',
            'detalles.*.impuestos.*.tarifa' => 'tarifa',
            'detalles.*.impuestos.*.base_imponible' => 'base imponible',
            'detalles.*.impuestos.*.valor' => 'valor del impuesto',
            'info_adicional.*.nombre' => 'nombre del campo adicional',
            'info_adicional.*.valor' => 'valor del campo adicional',
            'retenciones.*.codigo' => 'código de retención',
            'retenciones.*.porcentaje' => 'porcentaje de retención',
            'retenciones.*.valor' => 'valor de retención'
        ];
    }

    /**
     * Prepara los datos para la validación.
     */
    protected function prepareForValidation()
    {
        if ($this->has('fecha_emision')) {
            $this->merge([
                'periodo_fiscal' => date('m/Y', strtotime($this->fecha_emision))
            ]);
        }
    }
}
