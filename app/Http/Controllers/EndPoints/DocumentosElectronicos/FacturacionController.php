<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;

use App\Services\DocumentService;
use App\Services\SoapSriService;
use Exception;
use App\Exceptions\{FacturacionException, ImpuestoInvalidoException};
//use App\Http\Controllers\Api\Request;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacturaRequest;
use App\Models\Factura;
use App\Services\FacturacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use SoapClient;

use function App\Http\Controllers\Api\route;
/**
 * @OA\Tag(
 *     name="Facturación",
 *     description="Endpoints para la gestión de facturas electrónicas"
 * )
 */
class FacturacionController extends Controller
{
    protected $facturacionService;

    public function __construct(FacturacionService $facturacionService)
    {
        $this->facturacionService = $facturacionService;

    }

    /**
     * @OA\Post(
     *     path="/facturacion",
     *     summary="Crear nueva factura electrónica",
     *     description="Crea y procesa una nueva factura electrónica según normativa SRI",
     *     operationId="crearFactura",
     *     tags={"Facturación"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la factura",
     *         @OA\JsonContent(
     *             required={"empresa_id","establecimiento_id","punto_emision_id","comprador","detalles","formas_pago"},
     *             @OA\Property(property="empresa_id", type="integer", example=1),
     *             @OA\Property(property="establecimiento_id", type="integer", example=1),
     *             @OA\Property(property="punto_emision_id", type="integer", example=1),
     *             @OA\Property(property="ambiente", type="integer", example=1),
     *             @OA\Property(property="fechaEmision", type="string", example="17-01-2025"),
     *             @OA\Property(
     *                 property="comprador",
     *                 type="object",
     *                 required={"tipo_identificacion","identificacion","razon_social","direccion","email"},
     *                 @OA\Property(
     *                     property="tipo_identificacion",
     *                     type="string",
     *                     enum={"04", "05", "06", "07", "08"},
     *                     example="04",
     *                     description="04=RUC, 05=CEDULA, 06=PASAPORTE, 07=CONSUMIDOR_FINAL, 08=IDENTIFICACION_EXTERIOR"
     *                 ),
     *                 @OA\Property(property="identificacion", type="string", example="0992877878001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                 @OA\Property(property="direccion", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="email", type="string", format="email", example="cliente@ejemplo.com")
     *             ),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"codigo_principal","descripcion","cantidad","precio_unitario","descuento","impuestos","tipo_producto"},
     *                     @OA\Property(property="codigo_principal", type="string", example="PRD001", maxLength=25),
     *                     @OA\Property(property="codigo_auxiliar", type="string", example="AUX001", maxLength=25),
     *                     @OA\Property(property="descripcion", type="string", example="Producto de prueba", maxLength=300),
     *                     @OA\Property(
     *                         property="tipo_producto",
     *                         type="string",
     *                         enum={
     *                             "NORMAL",
     *                             "MEDICINAS",
     *                             "CANASTA_BASICA",
     *                             "SERVICIOS_BASICOS",
     *                             "SERVICIOS_PROFESIONALES",
     *                             "EDUCACION",
     *                             "REGIMEN_SIMPLIFICADO",
     *                             "ESPECIAL",
     *                             "EXPORTACION"
     *                         },
     *                         example="NORMAL"
     *                     ),
     *                     @OA\Property(
     *                         property="cantidad",
     *                         type="number",
     *                         format="float",
     *                         example=2.000000,
     *                         minimum=0.000001,
     *                         maximum=999999999.999999
     *                     ),
     *                     @OA\Property(
     *                         property="precio_unitario",
     *                         type="number",
     *                         format="float",
     *                         example=100.000000,
     *                         minimum=0,
     *                         maximum=999999999.999999
     *                     ),
     *                     @OA\Property(
     *                         property="descuento",
     *                         type="number",
     *                         format="float",
     *                         example=10.00,
     *                         minimum=0,
     *                         maximum=999999999.99
     *                     ),
     *                     @OA\Property(
     *                         property="impuestos",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             required={"codigo","codigo_porcentaje","base_imponible","valor"},
     *                             @OA\Property(
     *                                 property="codigo",
     *                                 type="string",
     *                                 example="02",
     *                                 description="Código SRI del impuesto según tipos_impuestos"
     *                             ),
     *                             @OA\Property(
     *                                 property="codigo_porcentaje",
     *                                 type="string",
     *                                 example="4",
     *                                 description="Código SRI de la tarifa según tarifas_impuestos"
     *                             ),
     *                              @OA\Property(
     *                                 property="impuesto_tarifa",
     *                                 type="number",
     *                                 example=15.00,
     *                                 description="Tarifa del SRI según el codigo Impuesto "
     *                             ),
     *                             @OA\Property(
     *                                 property="base_imponible",
     *                                 type="number",
     *                                 format="float",
     *                                 example=190.00,
     *                                 minimum=0,
     *                                 maximum=999999999.99
     *                             ),
     *                             @OA\Property(
     *                                 property="valor",
     *                                 type="number",
     *                                 format="float",
     *                                 example=28.50,
     *                                 minimum=0,
     *                                 maximum=999999999.99
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="formas_pago",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"forma_pago","total"},
     *                     @OA\Property(
     *                         property="forma_pago",
     *                         type="string",
     *                         enum={"01", "15", "16", "17", "18", "19", "20", "21"},
     *                         example="01",
     *                         description="01=Efectivo, 15=Compensación, 16=Tarjeta Débito, 17=Dinero Electrónico, 18=Tarjeta Prepago, 19=Tarjeta Crédito, 20=Otros, 21=Endoso de Títulos"
     *                     ),
     *                     @OA\Property(
     *                         property="total",
     *                         type="number",
     *                         format="float",
     *                         example=276.00,
     *                         minimum=0,
     *                         maximum=999999999.99
     *                     ),
     *                     @OA\Property(property="plazo", type="integer", example=0, minimum=0),
     *                     @OA\Property(
     *                         property="unidad_tiempo",
     *                         type="string",
     *                         enum={"dias", "meses"},
     *                         example="dias"
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="detalles_adicionales",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"nombre","valor"},
     *                     @OA\Property(property="nombre", type="string", example="Vendedor", maxLength=300),
     *                     @OA\Property(property="valor", type="string", example="Juan Pérez", maxLength=300)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="propina",
     *                 type="number",
     *                 format="float",
     *                 example=0.00,
     *                 minimum=0,
     *                 maximum=999999999.99
     *             ),
     *             @OA\Property(
     *                 property="observacion",
     *                 type="string",
     *                 example="Factura de prueba",
     *                 maxLength=300
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Factura creada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Factura creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="clave_acceso",
     *                     type="string",
     *                     example="2311202301099287787800110010010000000011234567813"
     *                 ),
     *                 @OA\Property(
     *                     property="numero_factura",
     *                     type="string",
     *                     example="001-001-000000001"
     *                 ),
     *                 @OA\Property(
     *                     property="estado",
     *                     type="string",
     *                     example="CREADA",
     *                     enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}
     *                 ),
     *                 @OA\Property(property="url_pdf", type="string", format="uri"),
     *                 @OA\Property(property="url_xml", type="string", format="uri")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error en los datos de la factura"),
     *             @OA\Property(property="error", type="string"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 ),
     *                 example={
     *                     "comprador.tipo_identificacion": {
     *                         "El tipo de identificación no es válido"
     *                     },
     *                     "detalles.0.impuestos": {
     *                         "Los impuestos son requeridos"
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error interno del servidor"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(FacturaRequest $request): JsonResponse
    {


        try {
            $docSRI = new DocumentService();
            $docSRI->guardarDocumentoInicio($request->validated(), Carbon::now(), 'logs_facturas_recibidas_sin_procesar');
            // 1. Iniciar medición de tiempo
            $tiempoInicio = microtime(true);
            Log::channel('facturas')->info('datos recibidos', [
                $request->validated()
            ]);
            Log::channel('facturas')->info('Iniciando proceso de facturación', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_inicio' => $tiempoInicio
            ]);

            // 2. Procesar y crear factura
            $tiempoPreProceso = microtime(true);
            Log::channel('facturas')->info('Iniciando procesamiento de factura', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_transcurrido' => round(($tiempoPreProceso - $tiempoInicio) * 1000, 2) . ' ms'
            ]);

            $factura = $this->facturacionService->procesarFactura($request->validated());

            $tiempoPreAPI = microtime(true);
            Log::channel('facturas')->info('Factura creada exitosamente, iniciando envío al SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_transcurrido' => round(($tiempoPreAPI - $tiempoPreProceso) * 1000, 2) . ' ms',
                'clave_acceso' => $factura->claveAcceso
            ]);

            // 3. Inicializar servicio SOAP y verificar disponibilidad
            $wsd = 'http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl';
            $wdsURL = 'http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc';

            $soapService = new SoapSriService($wsd,$wdsURL);

            if (!$soapService->isConnected()) {
                Log::channel('facturas')->warning('Servicio SRI no disponible', [
                    'wsd' => $wsd,
                    'wsdURL' => $wdsURL,
                    'status' => $soapService->getStatus(),
                    'clave_acceso' => $factura->claveAcceso,
                    'tiempo_transcurrido' => round((microtime(true) - $tiempoInicio) * 1000, 2) . ' ms'
                ]);

                $docSRI->guardarDocumentoPendiente($factura->toArray(), $factura->id, 'facturas');
                $responseData = [
                    'success' => true,
                    'message' => 'Factura creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $factura->id,
                        'uuid' => $factura->uuid,
                        'clave_acceso' => 0,
                        'numero_factura' => implode('-', [
                            $factura->establecimiento->codigo,
                            $factura->puntoEmision->codigo,
                            $factura->secuencial
                        ]),
                        'estado' => $factura->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ];
                Log::channel('facturas')->info('Factura generada:', $responseData);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $factura->id,
                        'uuid' => $factura->uuid,
                        'clave_acceso' => $factura->claveAcceso,
                        'numero_factura' => implode('-', [
                            $factura->establecimiento->codigo,
                            $factura->puntoEmision->codigo,
                            $factura->secuencial
                        ]),
                        'estado' => $factura->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ], 201);
            }

            // 4. Enviar factura al SRI
            $tiempoEnvioSRI = microtime(true);

            $resultadoSoap = $soapService->enviarFactura($factura->toArray());
            $tiempoPostAPI = microtime(true);

            Log::channel('facturas')->info('Respuesta recibida del SRI', [
                'wsd' => $wsd,
                'wsdURL' => $wdsURL,
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_total_proceso' => round(($tiempoPostAPI - $tiempoInicio) * 1000, 2) . ' ms',
                'tiempo_api' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'success' => $resultadoSoap['success'],
                'status' => $resultadoSoap['status'],
                'clave_acceso' => $factura->claveAcceso
            ]);

            // 5. Preparar respuesta según el resultado del SRI
            $response = [
                'success' => true,
                'data' => [
                    'id' => $factura->id,
                    'uuid' => $factura->uuid,
                    'clave_acceso' => $resultadoSoap['data']['Clave_Acceso'],
                    'numero_factura' => implode('-', [
                        $factura->establecimiento->codigo,
                        $factura->puntoEmision->codigo,
                        $factura->secuencial
                    ]),
                    'estado' => $factura->estado,
                    'estado_sri' => $resultadoSoap['status']
                ]
            ];

            if (!$resultadoSoap['success']) {
                $response['warning'] = 'Factura creada pero con errores en el envío al SRI';
                $response['error_sri'] = $resultadoSoap['message'] ?? 'Error no especificado';

                Log::channel('facturas')->warning('Factura creada con errores en envío SRI', [
                    'wsd' => $wsd,
                    'wsdURL' => $wdsURL,
                    'clave_acceso' => $factura->claveAcceso,
                    'estado_sri' => $resultadoSoap['status'],
                    'error' => $resultadoSoap['message'] ?? 'Error no especificado'
                ]);

                return response()->json($response, 201);
            }

            $response['message'] = 'Factura creada y enviada exitosamente';
            return response()->json($response, 201);

        } catch (ImpuestoInvalidoException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en los impuestos de la factura',
                'error' => $e->getMessage()
            ], 422);

        } catch (FacturacionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la factura',
                'error' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::channel('facturas')->error('Error al crear factura', [
                'wsd' => $wsd,
                'wsdURL' => $wdsURL,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated(),
              //  'tiempo_total' => round((microtime(true) - $tiempoInicio) * 1000, 2) . ' ms'
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function enviarFactura($datosFactura): array
    {
        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode($datosFactura)
            ];

            $response = $this->soapClient->SERV_GENERAR_DOCUME_XML_FAC($params);

            if (isset($response->SERV_GENERAR_DOCUME_XML_FACResult)) {
                return [
                    'success' => true,
                    'data' => json_decode($response->SERV_GENERAR_DOCUME_XML_FACResult, true)
                ];
            }

            return [
                'success' => false,
                'message' => 'Respuesta inválida del servicio SOAP'
            ];

        } catch (Exception $e) {
            Log::channel('facturas')->error('Error al enviar factura al SRI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $datosFactura
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar factura al SRI',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @OA\Get(
     *     path="/facturacion/{claveAcceso}",
     *     summary="Consultar factura por clave de acceso",
     *     description="Obtiene el detalle completo de una factura usando su clave de acceso. La clave debe contener exactamente 49 dígitos numéricos.",
     *     operationId="consultarFactura",
     *     tags={"Facturación"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la factura (49 dígitos numéricos)",
     *         @OA\Schema(
     *          type="string",
     *          example="2311202301099287787800110010010000000011234567813",
     *          minLength=10,
     *          maxLength=49
     *      )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Factura encontrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="factura",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="clave_acceso", type="string", example="2311202301099287787800110010010000000011234567813"),
     *                     @OA\Property(property="estado", type="string", enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}, example="AUTORIZADA"),
     *                     @OA\Property(property="fecha_emision", type="string", format="date", example="2023-11-20"),
     *                     @OA\Property(property="razon_social_comprador", type="string", example="CONSUMIDOR FINAL"),
     *                     @OA\Property(property="identificacion_comprador", type="string", example="9999999999999"),
     *                     @OA\Property(property="total_sin_impuestos", type="number", format="float", example=100.00),
     *                     @OA\Property(property="total_descuento", type="number", format="float", example=0.00),
     *                     @OA\Property(property="total_impuestos", type="number", format="float", example=15.00),
     *                     @OA\Property(property="importe_total", type="number", format="float", example=115.00),
     *                     @OA\Property(
     *                         property="detalles",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="descripcion", type="string", example="Producto de prueba"),
     *                             @OA\Property(property="cantidad", type="number", format="float", example=1),
     *                             @OA\Property(property="precio_unitario", type="number", format="float", example=100.00),
     *                             @OA\Property(property="descuento", type="number", format="float", example=0.00),
     *                             @OA\Property(property="total", type="number", format="float", example=100.00),
     *                             @OA\Property(
     *                                 property="impuestos",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="codigo", type="string", example="2"),
     *                                     @OA\Property(property="codigo_porcentaje", type="string", example="2"),
     *                                     @OA\Property(property="base_imponible", type="number", example=100.00),
     *                                     @OA\Property(property="valor", type="number", example=15.00)
     *                                 )
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="pagos",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="forma_pago", type="string", example="01"),
     *                             @OA\Property(property="total", type="number", format="float", example=115.00),
     *                             @OA\Property(property="plazo", type="integer", example=0),
     *                             @OA\Property(property="unidad_tiempo", type="string", example="dias")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="detalles_adicionales",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="nombre", type="string", example="Email"),
     *                             @OA\Property(property="valor", type="string", example="cliente@ejemplo.com")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Formato de clave de acceso inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Formato de clave de acceso inválido"),
     *             @OA\Property(property="error", type="string", example="La clave de acceso debe contener exactamente 49 dígitos numéricos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Factura no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="message", type="string", example="Factura no encontrada"),
     *             @OA\Property(property="error", type="string", example="No se encontró la factura con la clave de acceso proporcionada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No tiene permisos para realizar esta acción")
     *         )
     *     )
     * )
     */
    public function show(string $claveAcceso): JsonResponse
    {
        if (!preg_match('/^[0-9]{49}$/', $claveAcceso)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Formato de clave de acceso inválido',
                'error' => 'La clave de acceso debe contener exactamente 49 dígitos numéricos'
            ], 400);
        }

        try {
            $factura = $this->facturacionService->consultarFactura($claveAcceso);

            return response()->json([
                'success' => true,
                'data' => [
                    'factura' => $factura->load([
                        'detalles.impuestos',
                        'impuestos',
                        'pagos',
                        'detallesAdicionales',
                        'estados'
                    ])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'=> null,
                'message' => 'Factura no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/facturacion/{claveAcceso}/anular",
     *     summary="Anular factura",
     *     description="Anula una factura existente",
     *     operationId="anularFactura",
     *     tags={"Facturación"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la factura a anular",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motivo"},
     *             @OA\Property(property="motivo", type="string", minLength=10, maxLength=300,
     *                 example="Anulación solicitada por el cliente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Factura anulada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede anular la factura"
     *     )
     * )
     */
    public function anular(string $claveAcceso, Request $request): JsonResponse
    {
        try {

            $factura = Factura::where('claveAcceso', $claveAcceso)->firstOrFail();

            $request->validate([
                'motivo' => 'required|string|min:10|max:300'
            ]);

            $this->facturacionService->anularFactura(
                $factura,
                $request->motivo,
                $request->user()->name
            );

            return response()->json([
                'success' => true,
                'message' => 'Factura anulada correctamente',
                'data' => [
                    'estado' => $factura->estado,
                    'motivo_anulacion' => $request->motivo
                ]
            ]);

        } catch (FacturacionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede anular la factura',
                'error' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::channel('facturas')->error('Error al anular factura', [
                'clave_acceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => 'No se pudo anular la factura'
            ], 500);
        }
    }




    /**
     * @OA\Get(
     *     path="/api/facturacion/{claveAcceso}/xml",
     *     summary="Descargar XML de la factura",
     *     description="Genera y descarga el XML de una factura",
     *     operationId="descargarXML",
     *     tags={"Facturación"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="XML de la factura",
     *         @OA\MediaType(
     *             mediaType="application/xml"
     *         )
     *     )
     * )
     */
    public function descargarXML(string $claveAcceso): JsonResponse|Response
    {
        try {
            $factura = $this->facturacionService->consultarFactura($claveAcceso);
            $xml = $factura->generarXML();

            return response($xml)
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'attachment; filename="' . $claveAcceso . '.xml"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/facturacion/{claveAcceso}/estado-sri",
     *     summary="Consultar estado en SRI",
     *     description="Consulta el estado de una factura en el SRI",
     *     operationId="consultarEstadoSRI",
     *     tags={"Facturación"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado de la factura en el SRI"
     *     )
     * )
     */
    public function consultarEstadoSRI(string $claveAcceso): JsonResponse
    {
        try {
            $resultado = $this->facturacionService->consultarEstadoSRI($claveAcceso);

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado en SRI',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
