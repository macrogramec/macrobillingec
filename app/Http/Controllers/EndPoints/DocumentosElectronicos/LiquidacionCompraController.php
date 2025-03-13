<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;

use App\Http\Controllers\Api\DocumentosControllers;
use App\Http\Controllers\Controller;
use App\Models\Retencion;
use App\Services\{DocumentService, LiquidacionCompraService, SoapSriService};
use App\Http\Requests\LiquidacionCompraRequest;
use App\Models\LiquidacionCompra;
use Illuminate\Http\{JsonResponse, Response, Request};
use Illuminate\Support\Facades\{Log, Cache, DB};
use Carbon\Carbon;
/**
 * @OA\Tag(
 *     name="Liquidaciones de Compra",
 *     description="Endpoints para gestión de liquidaciones de compra electrónicas"
 * )
 */
class LiquidacionCompraController extends Controller
{
    protected LiquidacionCompraService $liquidacionService;
    protected SoapSriService $soapService;
    protected int $tiempoCache = 300; // 5 minutos

    public function __construct(
        LiquidacionCompraService $liquidacionService,
        SoapSriService $soapService
    ) {
        $this->liquidacionService = $liquidacionService;
        $this->soapService = $soapService;
    }

    /**
     * @OA\Post(
     *     path="/liquidaciones-compra",
     *     summary="Crear liquidación de compra",
     *     description="Crea y procesa una nueva liquidación de compra electrónica según normativa SRI",
     *     operationId="crearLiquidacionCompra",
     *     tags={"Liquidaciones de Compra"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la liquidación de compra",
     *         @OA\JsonContent(
     *             required={"empresa_id","establecimiento_id","punto_emision_id","ambiente","tipo_emision","version","fecha_emision","proveedor","detalles"},
     *             @OA\Property(property="empresa_id", type="integer", example=1),
     *             @OA\Property(property="establecimiento_id", type="integer", example=1),
     *             @OA\Property(property="punto_emision_id", type="integer", example=1),
     *             @OA\Property(property="ambiente", type="string", enum={"1", "2"}, example="1", description="1: Pruebas, 2: Producción"),
     *             @OA\Property(property="tipo_emision", type="string", enum={"1"}, example="1", description="1: Normal"),
     *             @OA\Property(property="version", type="string", example="1.0.0"),
     *             @OA\Property(property="fecha_emision", type="string", format="date", example="2024-01-17"),
     *             @OA\Property(
     *                 property="proveedor",
     *                 type="object",
     *                 required={"tipo_identificacion","identificacion","razon_social","direccion","email","tipo","regimen"},
     *                 @OA\Property(
     *                     property="tipo_identificacion",
     *                     type="string",
     *                     enum={"04", "05", "06", "07", "08"},
     *                     example="04",
     *                     description="04=RUC, 05=CEDULA, 06=PASAPORTE, 07=CONSUMIDOR_FINAL, 08=IDENTIFICACION_EXTERIOR"
     *                 ),
     *                 @OA\Property(property="identificacion", type="string", example="0921212121001"),
     *                 @OA\Property(property="razon_social", type="string", example="PROVEEDOR EJEMPLO S.A."),
     *                 @OA\Property(property="direccion", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="email", type="string", format="email", example="proveedor@ejemplo.com"),
     *                 @OA\Property(property="telefono", type="string", format="telefono", example="0909090909"),
     *                 @OA\Property(property="tipo", type="string", enum={"sociedad", "persona_natural"}, example="persona_natural"),
     *                 @OA\Property(property="regimen", type="string", enum={"rimpe", "general"}, example="rimpe")
     *             ),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"codigo_principal","descripcion","tipo_producto","cantidad","precio_unitario","impuestos"},
     *                     @OA\Property(property="codigo_principal", type="string", example="PRD001", maxLength=25),
     *                     @OA\Property(property="codigo_auxiliar", type="string", example="AUX001", maxLength=25),
     *                     @OA\Property(property="descripcion", type="string", example="Producto de prueba", maxLength=300),
     *                     @OA\Property(
     *                         property="tipo_producto",
     *                         type="string",
     *                         enum={"NORMAL", "MEDICINAS", "CANASTA_BASICA", "SERVICIOS_BASICOS", "TURISMO", "CONSTRUCCION", "TRANSPORTE", "EXPORTACION"},
     *                         example="NORMAL"
     *                     ),
     *                     @OA\Property(
     *                         property="cantidad",
     *                         type="number",
     *                         format="float",
     *                         example=2,
     *                         minimum=0.000001,
     *                         maximum=999999999.999999
     *                     ),
     *                     @OA\Property(
     *                         property="precio_unitario",
     *                         type="number",
     *                         format="float",
     *                         example=100,
     *                         minimum=0,
     *                         maximum=999999999.999999
     *                     ),
     *                     @OA\Property(
     *                         property="descuento",
     *                         type="number",
     *                         format="float",
     *                         example=10,
     *                         minimum=0,
     *                         maximum=999999999.99
     *                     ),
     *                     @OA\Property(
     *                         property="impuestos",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             required={"codigo","codigo_porcentaje","base_imponible","tarifa","valor"},
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
     *                             @OA\Property(
     *                                 property="base_imponible",
     *                                 type="number",
     *                                 format="float",
     *                                 example=190,
     *                                 minimum=0,
     *                                 maximum=999999999.99
     *                             ),
     *                             @OA\Property(
     *                                 property="tarifa",
     *                                 type="number",
     *                                 format="float",
     *                                 example=15,
     *                                 description="Porcentaje de la tarifa del impuesto"
     *                             ),
     *                             @OA\Property(
     *                                 property="valor",
     *                                 type="number",
     *                                 format="float",
     *                                 example=28.5,
     *                                 minimum=0,
     *                                 maximum=999999999.99
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *                  @OA\Property(
     *                  property="formas_pago",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      required={"forma_pago","total"},
     *                      @OA\Property(
     *                          property="forma_pago",
     *                          type="string",
     *                          enum={"01", "15", "16", "17", "18", "19", "20", "21"},
     *                          example="01",
     *                          description="01=Efectivo, 15=Compensación, 16=Tarjeta Débito, 17=Dinero Electrónico, 18=Tarjeta Prepago, 19=Tarjeta Crédito, 20=Otros, 21=Endoso de Títulos"
     *                      ),
     *                      @OA\Property(
     *                          property="total",
     *                          type="number",
     *                          format="float",
     *                          example=276.00,
     *                          minimum=0,
     *                          maximum=999999999.99
     *                      ),
     *                      @OA\Property(property="plazo", type="integer", example=0, minimum=0),
     *                      @OA\Property(
     *                          property="unidad_tiempo",
     *                          type="string",
     *                          enum={"dias", "meses"},
     *                          example="dias"
     *                      )
     *                  )
     *              ),
     *             @OA\Property(
     *                 property="info_adicional",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"nombre","valor"},
     *                     @OA\Property(property="nombre", type="string", example="Referencia", maxLength=300),
     *                     @OA\Property(property="valor", type="string", example="Orden #123", maxLength=300)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Liquidación de compra creada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liquidación de compra creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="codDoc", type="string", example="03"),
     *                 @OA\Property(property="version", type="string", example="1.0.0"),
     *                 @OA\Property(
     *                     property="clave_acceso",
     *                     type="string",
     *                     example="2311202301099287787800110010010000000011234567813"
     *                 ),
     *                 @OA\Property(
     *                     property="numero",
     *                     type="string",
     *                     example="001-001-000000001"
     *                 ),
     *                 @OA\Property(
     *                     property="estado",
     *                     type="string",
     *                     example="CREADA",
     *                     enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(LiquidacionCompraRequest $request): JsonResponse
    {
        try {
            $tiempoInicio = microtime(true);
            Log::channel('liquidacion')->info('Iniciando creación de liquidación de compra', [
                'tiempo_inicio' => $tiempoInicio,
                'ip' => $request->ip()
            ]);

            // Obtener datos validados
            $datos = $request->validated();
            $docSRI = new DocumentService();
            $docSRI->guardarDocumentoInicio($request->validated(), Carbon::now(), 'logs_liquidaciones_recibidas_sin_procesar');
            // Obtener datos de empresa, establecimiento y punto emisión en una sola consulta
            $datosEmision = DB::select('CALL API_OBTENER_DATOS_FACTURACION(?, ?, ?,?,?)', [
                $datos['empresa_id'],
                $datos['establecimiento_id'],
                $datos['punto_emision_id'],
                $datos['ambiente'],
                '03'
            ]);
           // dd($datosEmision);
            if (empty($datosEmision)) {
                throw new \Exception('No se encontraron los datos de emisión necesarios');
            }

            $datosEmision = $datosEmision[0];

            // Preparar datos para el servicio
            $datos['empresa'] = [
                'id' => $datosEmision->empresa_id,
                'ruc' => $datosEmision->ruc,
                'razon_social' => $datosEmision->razon_social,
                'nombre_comercial' => $datosEmision->nombre_comercial,
                'direccion_matriz' => $datosEmision->direccion_matriz,
                'obligado_contabilidad' => $datosEmision->obligado_contabilidad,
            ];

            $datos['establecimiento'] = [
                'id' => $datosEmision->establecimiento_id,
                'codigo' => $datosEmision->establecimiento_codigo,
                'direccion' => $datosEmision->establecimiento_direccion,
            ];

            $datos['punto_emision'] = [
                'id' => $datosEmision->punto_emision_id,
                'codigo' => $datosEmision->punto_emision_codigo
            ];

            // Procesar liquidación
            $liquidacion = $this->liquidacionService->procesarLiquidacion($datos);


            $docSRI->guardarDocumentoPendiente($liquidacion->toArray(), $liquidacion->id, 'liquidaciones_compra');
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'liquidacion' => $liquidacion->toArray()
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];

            Log::channel('liquidacion')->info('data enviada', [
                'data' => $params
            ]);
            $wsd = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl";
            $wdsURL = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc";
            $soapService = new SoapSriService($wsd, $wdsURL);

            // 8. Verificar disponibilidad del servicio SRI
            if (!$soapService->isConnected()) {
                Log::channel('liquidacion')->warning('Servicio SRI no disponible', [
                    'status' => $soapService->getStatus(),
                    'clave_acceso' => $liquidacion->clave_acceso,
                ]);

                $responseData = [
                    'success' => true,
                    'message' => 'Liquidación creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $liquidacion->id,
                        'uuid' => $liquidacion->uuid,
                        'clave_acceso' => $liquidacion->clave_acceso,
                        'numero_documento' => $liquidacion->estab . '-' . $liquidacion->pto_emi . '-' . $liquidacion->secuencial,
                        'estado' => $liquidacion->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ];

                Log::channel('liquidacion')->info('Liquidación generada:', $responseData);
                return response()->json($responseData, 201);
            }

            // 9. Enviar nota de crédito al SRI
            $tiempoEnvioSRI = microtime(true);

            $resultadoSoap = $soapService->enviarLiquidacion($liquidacion->toArray());
            $tiempoPostAPI = microtime(true);


            Log::channel('liquidacion')->info('Liquidación de Compra generada:', ['data' => $liquidacion]);

            Log::channel('liquidacion')->info('Respuesta recibida del SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_api' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'success' => $resultadoSoap['success'],
                'status' => $resultadoSoap['status'],

            ]);

            // 10. Preparar respuesta
            $response = [
                'success' => true,
                'data' => [
                    'id' => $liquidacion->id,
                    'uuid' => $liquidacion->uuid,
                    'clave_acceso' => $resultadoSoap['data']['Clave_Acceso'],
                    'numero_documento' => $liquidacion->estab . '-' . $liquidacion->ptoemi . '-' . $liquidacion->secuencial,
                    'estado' => $liquidacion->estado,
                    'estado_sri' => $resultadoSoap['status'],
                    'data' => $liquidacion
                ]
            ];

            // 11. Manejar respuesta del SRI
            if (!$resultadoSoap['success']) {
                $response['warning'] = 'Retencion creada pero con errores en el envío al SRI';
                $response['error_sri'] = $resultadoSoap['message'] ?? 'Error no especificado';

                Log::channel('liquidacion')->warning('Retencion creada con errores en envío SRI', [
                    'clave_acceso' => $liquidacion->clave_acceso,
                    'estado_sri' => $resultadoSoap['status'],
                    'error' => $resultadoSoap['message'] ?? 'Error no especificado'
                ]);

                return response()->json($response, 201);
            }

            // 12. Respuesta exitosa
            $response['message'] = 'Retencion creada y enviada exitosamente';

            Log::channel('liquidacion')->info('Nota de crédito procesada exitosamente', [
                'clave_acceso' => $liquidacion->clave_acceso,
            ]);

            return response()->json($response, 201);





            // Enviar al SRI de manera asíncrona usando un job
         //   dispatch(new ProcesarLiquidacionSRIJob($liquidacion));
            /*
            return response()->json([
                'success' => true,
                'message' => 'Liquidación de compra creada exitosamente',
                'data' => [
                    'liquidacion' => $liquidacion,
                    'id' => $liquidacion->id,
                    'codDoc' => $liquidacion->cod_doc,
                    'version' => $liquidacion->version,
                    'clave_acceso' => $liquidacion->clave_acceso,
                    'numero' => "{$liquidacion->estab}-{$liquidacion->pto_emi}-{$liquidacion->secuencial}",
                    'estado' => $liquidacion->estado,
                    'urls' => [
                        'pdf' => "",
                        'xml' => ""
                    ]
                ]
            ], 201);

            */
        } catch (\Exception $e) {
            Log::channel('liquidacion')->error('Error al crear liquidación de compra', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'datos' => $request->validated()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la liquidación de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/liquidaciones-compra/{claveAcceso}",
     *     summary="Consultar liquidación de compra por clave de acceso",
     *     description="Obtiene el detalle completo de una liquidación de compra usando su clave de acceso. La clave debe contener exactamente 49 dígitos numéricos.",
     *     operationId="consultarLiquidacionCompra",
     *     tags={"Liquidaciones de Compra"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la liquidación (49 dígitos numéricos)",
     *         @OA\Schema(
     *             type="string",
     *             example="2311202301099287787800110010010000000011234567813",
     *             pattern="^[0-9]{49}$"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liquidación encontrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="liquidacion",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="clave_acceso", type="string", example="2311202301099287787800110010010000000011234567813"),
     *                     @OA\Property(property="estado", type="string", enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}, example="AUTORIZADA"),
     *                     @OA\Property(property="fecha_emision", type="string", format="date", example="2023-11-20"),
     *                     @OA\Property(property="razon_social_proveedor", type="string", example="PROVEEDOR EJEMPLO S.A."),
     *                     @OA\Property(property="identificacion_proveedor", type="string", example="0992877878001"),
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
     *                             @OA\Property(property="codigo_principal", type="string", example="PRD001"),
     *                             @OA\Property(property="descripcion", type="string", example="Producto o servicio de prueba"),
     *                             @OA\Property(property="cantidad", type="number", format="float", example=1),
     *                             @OA\Property(property="precio_unitario", type="number", format="float", example=100.00),
     *                             @OA\Property(property="descuento", type="number", format="float", example=0.00),
     *                             @OA\Property(property="precio_total_sin_impuesto", type="number", format="float", example=100.00),
     *                             @OA\Property(
     *                                 property="impuestos",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     type="object",
     *                                     @OA\Property(property="codigo", type="string", example="2"),
     *                                     @OA\Property(property="codigo_porcentaje", type="string", example="2"),
     *                                     @OA\Property(property="tarifa", type="number", example=15),
     *                                     @OA\Property(property="base_imponible", type="number", example=100.00),
     *                                     @OA\Property(property="valor", type="number", example=15.00)
     *                                 )
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="retenciones",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="codigo", type="string", example="1"),
     *                             @OA\Property(property="codigo_porcentaje", type="string", example="344"),
     *                             @OA\Property(property="porcentaje", type="number", example=2),
     *                             @OA\Property(property="base_imponible", type="number", example=100.00),
     *                             @OA\Property(property="valor_retenido", type="number", example=2.00)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="estados",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="estado_actual", type="string", example="AUTORIZADA"),
     *                             @OA\Property(property="fecha", type="string", format="date-time"),
     *                             @OA\Property(property="usuario", type="string", example="Sistema")
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
     *             @OA\Property(property="message", type="string", example="La clave de acceso debe contener exactamente 49 dígitos numéricos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Liquidación no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al consultar la liquidación de compra"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error interno del servidor"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(string $claveAcceso): JsonResponse
    {
        try {
            $cacheKey = "liquidacion_compra_{$claveAcceso}";

            // Intentar obtener del cache
            $liquidacion = Cache::remember($cacheKey, $this->tiempoCache, function() use ($claveAcceso) {
                return LiquidacionCompra::where('clave_acceso', $claveAcceso)
                    ->with([
                        'detalles' => fn($q) => $q->with(['impuestos' => fn($q) => $q->where('activo', true)]),
                        'retenciones',
                        'estados' => fn($q) => $q->latest()->take(5),
                        'detallesAdicionales'
                    ])
                    ->firstOrFail();
            });

            return response()->json([
                'success' => true,
                'data' => $liquidacion
            ]);

        } catch (\Exception $e) {
            Log::channel('liquidacion')->error('Error al consultar liquidación', [
                'clave_acceso' => $claveAcceso,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar la liquidación de compra',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/liquidaciones-compra/{claveAcceso}/pdf-download",
     *     summary="Descargar PDF",
     *     description="Descarga el PDF de una Liquidacion de Compra",
     *     operationId="descargarPDFLiquidacionCompra",
     *     tags={"Liquidaciones de Compra"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             pattern="^[0-9]{49}$"
     *         )
     *     ),
     *     @OA\Parameter(
     *          name="formato",
     *          in="query",
     *          required=false,
     *          description="Formato de respuesta: 'base64', 'binario' o 'url'",
     *          @OA\Schema(type="string", enum={"base64", "binario", "url"}, default="base64")
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="RIDE obtenido exitosamente",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="success", type="boolean", example=true),
     *                  @OA\Property(property="base64", type="string", description="Contenido del PDF en base64 (solo si formato=base64)")
     *              )
     *          ),
     *          @OA\MediaType(
     *              mediaType="application/pdf",
     *              @OA\Schema(type="string", format="binary")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Documento no encontrado o en proceso de generación",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="mensaje", type="string", example="RIDE EN PROCESO DE GENERACIÓN")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error al obtener el archivo",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="mensaje", type="string", example="Error al obtener el archivo")
     *          )
     *      )
     *  )
     */
    public function descargarPDF(string $claveAcceso, Request $request)
    {
        try {
            $factura = LiquidacionCompra::where('clave_acceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Simplemente retornar la respuesta que viene de obtenerRideFormatos
            return $documentos->obtenerRideFormatos($factura->uuid, $formato);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Liquidacion de Compra no encontrada',
                'error' => 'No se encontró la Liquidacion con la clave de acceso proporcionada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener RIDE',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/liquidaciones-compra/{claveAcceso}/xml",
     *     summary="Descargar XML",
     *     description="Descarga el XML de una Liquidación de Compra",
     *     operationId="descargarXMLLiquidacionCompra",
     *     tags={"Liquidaciones de Compra"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             pattern="^[0-9]{49}$"
     *         )
     *     ),
     *     @OA\Parameter(
     *          name="formato",
     *          in="query",
     *          required=false,
     *          description="Formato de respuesta: 'base64', 'binario' o 'url'",
     *          @OA\Schema(type="string", enum={"base64", "binario", "url"}, default="base64")
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="XML obtenido exitosamente",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(property="success", type="boolean", example=true),
     *                  @OA\Property(property="base64", type="string", description="Contenido del XML en base64 (solo si formato=base64)")
     *              )
     *          ),
     *          @OA\MediaType(
     *              mediaType="application/xml",
     *              @OA\Schema(type="string", format="binary")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Documento no encontrado o en proceso de generación",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="mensaje", type="string", example="XML EN PROCESO DE GENERACIÓN")
     *          )
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Error al obtener el archivo",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="mensaje", type="string", example="Error al obtener el archivo")
     *          )
     *      )
     *  )
     */
    public function descargarXML(string $claveAcceso, Request $request)
    {
        try {
            $liquidacion = LiquidacionCompra::where('clave_acceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Llamar al método obtenerDocumento con tipo 'xml'
            return $documentos->obtenerDocumento($liquidacion->uuid, $formato, 'xml');

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Liquidación de Compra no encontrada',
                'error' => 'No se encontró la Liquidación con la clave de acceso proporcionada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/liquidaciones-compra/{claveAcceso}/anular",
     *     summary="Anular liquidación de compra",
     *     description="Anula una liquidación de compra existente. Solo se pueden anular liquidaciones en estado CREADA o AUTORIZADA. La anulación debe incluir un motivo detallado.",
     *     operationId="anularLiquidacionCompra",
     *     tags={"Liquidaciones de Compra"},
     *     security={{"oauth2": {"admin"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la liquidación a anular (49 dígitos numéricos)",
     *         @OA\Schema(
     *             type="string",
     *             pattern="^[0-9]{49}$",
     *             example="2311202301099287787800110010010000000011234567813"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motivo"},
     *             @OA\Property(
     *                 property="motivo",
     *                 type="string",
     *                 minLength=10,
     *                 maxLength=300,
     *                 description="Motivo detallado de la anulación",
     *                 example="Datos incorrectos del proveedor registrados en el documento"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liquidación anulada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liquidación anulada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="estado", type="string", example="ANULADA"),
     *                 @OA\Property(
     *                     property="fecha_anulacion",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-01-19T14:30:00-05:00"
     *                 ),
     *                 @OA\Property(
     *                     property="motivo",
     *                     type="string",
     *                     example="Datos incorrectos del proveedor registrados en el documento"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación en la clave de acceso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="La clave de acceso debe contener exactamente 49 dígitos numéricos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Liquidación no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Liquidación no encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o liquidación no anulable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No se puede anular la liquidación"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="La liquidación solo puede ser anulada en estado CREADA o AUTORIZADA"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al anular la liquidación de compra"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function anular(Request $request, string $claveAcceso): JsonResponse
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:10|max:300'
            ]);

            // Obtener liquidación con lock para evitar condiciones de carrera
            $liquidacion = DB::transaction(function () use ($claveAcceso) {
                return LiquidacionCompra::where('clave_acceso', $claveAcceso)
                    ->lockForUpdate()
                    ->firstOrFail();
            });

            $this->liquidacionService->anular($liquidacion, $request->motivo, $request->user()->name);




            return response()->json([
                'success' => true,
                'message' => 'Liquidación anulada exitosamente',
                'data' => [
                    'estado' => $liquidacion->estado,
                    'fecha_anulacion' => $liquidacion->updated_at->format('Y-m-d H:i:s'),
                    'motivo' => $request->motivo
                ]
            ]);

        } catch (\Exception $e) {
            Log::channel('liquidacion')->error('Error al anular liquidación', [
                'clave_acceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular la liquidación de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function autorizar(string $claveAcceso): JsonResponse
    {
        try {
            // Validar estado actual con lock
            $liquidacion = DB::transaction(function () use ($claveAcceso) {
                $liquidacion = LiquidacionCompra::where('clave_acceso', $claveAcceso)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($liquidacion->estado === 'ANULADA') {
                    throw new \Exception('No se puede autorizar una liquidación anulada');
                }

                return $liquidacion;
            });

            // Procesar autorización de manera asíncrona
            dispatch(new AutorizarLiquidacionJob($liquidacion));

            return response()->json([
                'success' => true,
                'message' => 'Proceso de autorización iniciado',
                'data' => [
                    'estado' => $liquidacion->estado,
                    'clave_acceso' => $liquidacion->clave_acceso
                ]
            ]);

        } catch (\Exception $e) {
            Log::channel('liquidacion')->error('Error al autorizar liquidación', [
                'clave_acceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al autorizar la liquidación de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function consultarEstado(string $claveAcceso): JsonResponse
    {
        try {
            $cacheKey = "estado_liquidacion_{$claveAcceso}";

            // Intentar obtener estado del cache
            $estado = Cache::remember($cacheKey, 60, function () use ($claveAcceso) {
                $liquidacion = LiquidacionCompra::where('clave_acceso', $claveAcceso)
                    ->with(['estados' => fn($q) => $q->latest()->first()])
                    ->firstOrFail();

                return [
                    'estado' => $liquidacion->estado,
                    'estado_sri' => $liquidacion->estados->first()->estado_sri ?? null,
                    'fecha_proceso' => $liquidacion->estados->first()->created_at ?? null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $estado
            ]);

        } catch (\Exception $e) {
            Log::channel('liquidacion')->error('Error al consultar estado', [
                'clave_acceso' => $claveAcceso,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado de la liquidación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
