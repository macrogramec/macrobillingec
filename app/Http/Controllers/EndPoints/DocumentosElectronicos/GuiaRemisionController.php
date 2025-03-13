<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;

use App\Http\Controllers\Api\DocumentosControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuiaRemisionRequest;
use App\Services\DocumentService;
use App\Services\SoapSriService;
use App\Models\{Empresa, Establecimiento, GuiaRemision, LiquidacionCompra, PuntoEmision};
use App\Services\GuiaRemisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, Cache, DB};
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Guías de Remisión",
 *     description="Endpoints para gestión de guías de remisión electrónicas"
 * )
 */
class GuiaRemisionController extends Controller
{
    protected $guiaRemisionService;

    public function __construct(GuiaRemisionService $guiaRemisionService)
    {
        $this->guiaRemisionService = $guiaRemisionService;
    }

    /**
     * @OA\Post(
     *     path="/guias-remision",
     *     summary="Crear guía de remisión",
     *     description="Crea una nueva guía de remisión electrónica",
     *     operationId="crearGuiaRemision",
     *     tags={"Guías de Remisión"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la guía de remisión",
     *         @OA\JsonContent(
     *          required={"empresa_id", "establecimiento_id", "punto_emision_id", "ambiente", "tipo_emision", "fecha_ini_transporte", "fecha_fin_transporte", "transportista", "dir_partida", "destinatarios"},
     *      @OA\Property(property="empresa_id", type="integer", example=1),
     *      @OA\Property(property="establecimiento_id", type="integer", example=1),
     *      @OA\Property(property="punto_emision_id", type="integer", example=1),
     *      @OA\Property(property="ambiente", type="string", enum={"1", "2"}, example="1", description="1: Pruebas, 2: Producción"),
     *      @OA\Property(property="tipo_emision", type="string", enum={"1"}, example="1", description="1: Normal"),
     *      @OA\Property(property="fecha_ini_transporte", type="string", format="date", example="2024-03-15"),
     *      @OA\Property(property="fecha_fin_transporte", type="string", format="date", example="2024-03-16"),
     *      @OA\Property(
     *          property="transportista",
     *          type="object",
     *          required={"tipo_identificacion", "identificacion", "razon_social", "placa"},
     *          @OA\Property(property="tipo_identificacion", type="string", enum={"04", "05", "06", "07", "08"}, example="04"),
     *          @OA\Property(property="identificacion", type="string", example="0992877878001"),
     *          @OA\Property(property="razon_social", type="string", example="TRANSPORTES EXPRESO S.A."),
     *          @OA\Property(property="placa", type="string", example="ABC-1234")
     *      ),
     *      @OA\Property(property="dir_partida", type="string", example="Av. Principal 123, Quito"),
     *      @OA\Property(
     *          property="destinatarios",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              required={"identificacion", "razon_social", "direccion", "motivo_traslado", "detalles"},
     *              @OA\Property(property="identificacion", type="string", example="1716849140001"),
     *              @OA\Property(property="razon_social", type="string", example="EMPRESA DESTINO S.A."),
     *              @OA\Property(property="direccion", type="string", example="Av. 10 de Agosto y Colón, Guayaquil"),
     *              @OA\Property(property="email", type="string", example="correo@correo.com"),
     *              @OA\Property(property="motivo_traslado", type="string", example="Traslado de mercadería para venta"),
     *              @OA\Property(property="doc_aduanero", type="string", example="12345678"),
     *              @OA\Property(property="cod_establecimiento_destino", type="string", example="001"),
     *              @OA\Property(property="ruta", type="string", example="Quito - Ambato - Guayaquil"),
     *              @OA\Property(
     *                  property="doc_sustento",
     *                  type="object",
     *                  @OA\Property(property="tipo", type="string", enum={"01", "03", "04", "05", "06", "07"}, example="01"),
     *                  @OA\Property(property="numero", type="string", example="001-001-000000001"),
     *                  @OA\Property(property="autorizacion", type="string", example="1234567890"),
     *                  @OA\Property(property="fecha_emision", type="string", format="date", example="2024-03-14")
     *              ),
     *              @OA\Property(
     *                  property="detalles",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      required={"descripcion", "cantidad"},
     *                      @OA\Property(property="codigo_interno", type="string", example="PROD001"),
     *                      @OA\Property(property="codigo_adicional", type="string", example="AUX001"),
     *                      @OA\Property(property="descripcion", type="string", example="Producto de ejemplo"),
     *                      @OA\Property(property="cantidad", type="number", example=10.5),
     *                      @OA\Property(
     *                          property="detalles_adicionales",
     *                          type="array",
     *                          @OA\Items(
     *                              type="object",
     *                              required={"nombre", "valor"},
     *                              @OA\Property(property="nombre", type="string", example="Marca"),
     *                              @OA\Property(property="valor", type="string", example="XYZ")
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Property(
     *          property="info_adicional",
     *          type="array",
     *          @OA\Items(
     *              type="object",
     *              required={"nombre", "valor"},
     *              @OA\Property(property="nombre", type="string", example="Teléfono"),
     *              @OA\Property(property="valor", type="string", example="0991234567")
     *          )
     *      )
     *              )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Guía de remisión creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Guía de remisión creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clave_acceso", type="string", example="1203202301099287787800110010010000000011234567818"),
     *                 @OA\Property(property="numero", type="string", example="001-001-000000001"),
     *                 @OA\Property(property="estado", type="string", example="CREADA"),
     *                 @OA\Property(property="url_pdf", type="string", format="uri"),
     *                 @OA\Property(property="url_xml", type="string", format="uri")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Error en los datos de entrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(GuiaRemisionRequest $request): JsonResponse
    {
        try {
            $tiempoInicio = microtime(true);
            Log::channel('guias')->info('Iniciando creación de guía de remisión', [
                'tiempo_inicio' => $tiempoInicio,
                'ip' => $request->ip()
            ]);

            // Obtener datos validados
            $datos = $request->validated();
            $docSRI = new DocumentService();
            $docSRI->guardarDocumentoInicio($datos, Carbon::now(), 'logs_guias_recibidas_sin_procesar');
            // Obtener datos de empresa, establecimiento y punto emisión en una sola consulta
            $datosEmision = DB::select('CALL API_OBTENER_DATOS_FACTURACION(?, ?, ?,?,?)', [
                $datos['empresa_id'],
                $datos['establecimiento_id'],
                $datos['punto_emision_id'],
                $datos['ambiente'],
                '06' // Código para Guía de Remisión
            ]);

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

            // Procesar guía de remisión
            $guiaRemision = $this->guiaRemisionService->procesarGuiaRemision($datos);


            $docSRI->guardarDocumentoPendiente($guiaRemision->toArray(), $guiaRemision->id, 'guias_remision');
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'guiaRemision' => $guiaRemision->toArray()
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];

            Log::channel('guias')->info('data enviada', [
                'data' => $params
            ]);

            // Configuración del servicio SOAP
            $wsd = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl";
            $wdsURL = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc";
            $soapService = new SoapSriService($wsd, $wdsURL);

            // Verificar disponibilidad del servicio SRI
            if (!$soapService->isConnected()) {
                Log::channel('guias')->warning('Servicio SRI no disponible', [
                    'status' => $soapService->getStatus(),
                    'clave_acceso' => $guiaRemision->claveAcceso,
                ]);

                $responseData = [
                    'success' => true,
                    'message' => 'Guía de remisión creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $guiaRemision->id,
                        'uuid' => $guiaRemision->uuid,
                        'clave_acceso' => $guiaRemision->claveAcceso,
                        'numero_documento' => $guiaRemision->estab . '-' . $guiaRemision->ptoEmi . '-' . $guiaRemision->secuencial,
                        'estado' => $guiaRemision->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ];

                Log::channel('guias')->info('Guía de remisión generada:', $responseData);
                return response()->json($responseData, 201);
            }

            // Enviar guía de remisión al SRI
            $tiempoEnvioSRI = microtime(true);
            $resultadoSoap = $soapService->enviarGuiaRemision($guiaRemision->toArray());
            $tiempoPostAPI = microtime(true);

            Log::channel('guias')->info('Guía de remisión generada:', ['data' => $guiaRemision]);

            Log::channel('guias')->info('Respuesta recibida del SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_api' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'success' => $resultadoSoap['success'],
                'status' => $resultadoSoap['status'],
            ]);

            // Preparar respuesta
            $response = [
                'success' => true,
                'data' => [
                    'id' => $guiaRemision->id,
                    'uuid' => $guiaRemision->uuid,
                    'clave_acceso' => $resultadoSoap['data']['Clave_Acceso'],
                    'numero_documento' => $guiaRemision->estab . '-' . $guiaRemision->ptoemi . '-' . $guiaRemision->secuencial,
                    'estado' => $guiaRemision->estado,
                    'estado_sri' => $resultadoSoap['status']
                ]
            ];

            // Manejar respuesta del SRI
            if (!$resultadoSoap['success']) {
                $response['warning'] = 'Guía de remisión creada pero con errores en el envío al SRI';
                $response['error_sri'] = $resultadoSoap['message'] ?? 'Error no especificado';

                Log::channel('guias')->warning('Guía de remisión creada con errores en envío SRI', [
                    'clave_acceso' => $guiaRemision->claveAcceso,
                    'estado_sri' => $resultadoSoap['status'],
                    'error' => $resultadoSoap['message'] ?? 'Error no especificado'
                ]);

                return response()->json($response, 201);
            }

            // Respuesta exitosa
            $response['message'] = 'Guía de remisión creada y enviada exitosamente';

            Log::channel('guias')->info('Guía de remisión procesada exitosamente', [
                'clave_acceso' => $guiaRemision->claveAcceso,
            ]);

            return response()->json($response, 201);

        } catch (\Exception $e) {
            Log::channel('guias')->error('Error al crear guía de remisión', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'datos' => $request->validated()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/guias-remision/{claveAcceso}",
     *     summary="Obtener guía de remisión",
     *     description="Obtiene el detalle de una guía de remisión por su clave de acceso",
     *     operationId="obtenerGuiaRemision",
     *     tags={"Guías de Remisión"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la guía de remisión",
     *         @OA\Schema(
     *             type="string",
     *             pattern="^[0-9]{49}$",
     *             example="1203202301099287787800110010010000000011234567818"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Guía de remisión encontrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="clave_acceso", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="transportista", type="object"),
     *                 @OA\Property(property="destinatarios", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="estados", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Guía de remisión no encontrada")
     * )
     */
    public function show(string $claveAcceso): JsonResponse
    {
        try {
            $guiaRemision = $this->guiaRemisionService->consultarPorClaveAcceso($claveAcceso);

            return response()->json([
                'success' => true,
                'data' => $guiaRemision
            ]);

        } catch (\Exception $e) {
            Log::channel('guias')->error('Error al consultar guía de remisión', [
                'claveAcceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Guía de remisión no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/guias-remision/{claveAcceso}/pdf-download",
     *     summary="Descargar PDF",
     *     description="Descarga el PDF de una Guia de Remision",
     *     operationId="descargarPDFGuiaRemision",
     *     tags={"Guías de Remisión"},
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
            $factura = GuiaRemision::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Simplemente retornar la respuesta que viene de obtenerRideFormatos
            return $documentos->obtenerRideFormatos($factura->uuid, $formato);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Liquidacion de Compra no encontrada',
                'error' => 'No se encontró la guia de remision con la clave de acceso proporcionada'
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
     *     path="/guias-remision/{claveAcceso}/xml",
     *     summary="Descargar XML",
     *     description="Descarga el XML de una Guía de Remisión",
     *     operationId="descargarXMLGuiaRemision",
     *     tags={"Guías de Remisión"},
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
            $guiaRemision = GuiaRemision::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Llamar al método obtenerDocumento con tipo 'xml'
            return $documentos->obtenerDocumento($guiaRemision->uuid, $formato, 'xml');

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Guía de Remisión no encontrada',
                'error' => 'No se encontró la guía de remisión con la clave de acceso proporcionada'
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
     *     path="/guias-remision/{claveAcceso}/anular",
     *     summary="Anular guía de remisión",
     *     description="Anula una guía de remisión existente",
     *     operationId="anularGuiaRemision",
     *     tags={"Guías de Remisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", pattern="^[0-9]{49}$")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"motivo"},
     *             @OA\Property(
     *                 property="motivo",
     *                 type="string",
     *                 minLength=10,
     *                 example="Anulación solicitada por el emisor"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Guía de remisión anulada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Guía de remisión anulada exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Guía de remisión no encontrada"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function anular(Request $request, string $claveAcceso): JsonResponse
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:10'
            ]);

            $guiaRemision = GuiaRemision::where('claveAcceso', $claveAcceso)->firstOrFail();
            $this->guiaRemisionService->anularGuiaRemision($guiaRemision, $request->motivo);

            return response()->json([
                'success' => true,
                'message' => 'Guía de remisión anulada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::channel('guias')->error('Error al anular guía de remisión', [
                'claveAcceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al anular la guía de remisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
