<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;

use App\Http\Controllers\Api\DocumentosControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\RetencionRequest;
use App\Models\CodigoRetencion;
use App\Models\Empresa;
use App\Models\NotaCredito;
use App\Services\DocumentService;
use App\Services\RetencionService;
use App\Models\Retencion;
use App\Services\SoapSriService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Retenciones",
 *     description="Endpoints para gestión de retenciones electrónicas"
 * )
 */
class RetencionController extends Controller
{
    protected $retencionService;

    public function __construct(RetencionService $retencionService)
    {
        $this->retencionService = $retencionService;
    }

    /**
     * @OA\Get(
     *     path="/retenciones/{id_empresa}",
     *     summary="Listar retenciones",
     *     description="Obtiene un listado paginado de retenciones con filtros opcionales",
     *     operationId="listarRetenciones",
     *     tags={"Retenciones"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *          name="id_empresa",
     *          in="path",
     *          required=true,
     *          description="Identificador único de la empresa",
     *          @OA\Schema(
     *              type="integer",
     *              format="int64"
     *          )
     *     ),
     *     @OA\Parameter(
     *         name="fecha_desde",
     *         in="query",
     *         description="Fecha inicial (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="fecha_hasta",
     *         in="query",
     *         description="Fecha final (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="identificacion",
     *         in="query",
     *         description="Identificación del sujeto retenido",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Estado de la retención",
     *         @OA\Schema(type="string", enum={"CREADA", "AUTORIZADA", "ANULADA"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de retenciones",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="empresa_id", type="integer"),
     *                     @OA\Property(property="establecimiento_id", type="integer"),
     *                     @OA\Property(property="punto_emision_id", type="integer"),
     *                     @OA\Property(property="uuid", type="string"),
     *                     @OA\Property(property="estado", type="string"),
     *                     @OA\Property(property="ambiente", type="string"),
     *                     @OA\Property(property="clave_acceso", type="string"),
     *                     @OA\Property(property="fecha_emision", type="string", format="date"),
     *                     @OA\Property(property="periodo_fiscal", type="string"),
     *                     @OA\Property(property="identificacion_sujeto", type="string"),
     *                     @OA\Property(property="razon_social_sujeto", type="string"),
     *                     @OA\Property(property="total_retenido", type="number", format="float"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string", example="Retenciones obtenidas exitosamente")
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
     *         response=404,
     *         description="No encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Empresa no encontrada")
     *         )
     *     )
     * )
     */
    public function index($id_empresa, Request $request): JsonResponse
    {
        $id_empresa = (int) $id_empresa;
        // Verificar si la empresa existe (opcional, pero recomendado)
        if (!Empresa::find($id_empresa)) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $filters = $request->only([
            'fecha_desde',
            'fecha_hasta',
            'identificacion',
            'estado'
        ]);

        $retenciones = Retencion::query()
            ->where('empresa_id', $id_empresa)
            ->when(isset($filters['fecha_desde']), function($query) use ($filters) {
                return $query->where('fecha_emision', '>=', $filters['fecha_desde']);
            })
            ->when(isset($filters['fecha_hasta']), function($query) use ($filters) {
                return $query->where('fecha_emision', '<=', $filters['fecha_hasta']);
            })
            ->when(isset($filters['identificacion']), function($query) use ($filters) {
                return $query->where('identificacion_sujeto', $filters['identificacion']);
            })
            ->when(isset($filters['estado']), function($query) use ($filters) {
                return $query->where('estado', $filters['estado']);
            })
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $retenciones,
            'message' => 'Retenciones obtenidas exitosamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/retenciones/codigos-retencion",
     *     summary="Listar códigos de retención",
     *     description="Obtiene el listado de códigos de retención activos con filtros opcionales",
     *     operationId="listarCodigosRetencion",
     *     tags={"Retenciones"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="tipo_impuesto",
     *         in="query",
     *         description="Tipo de impuesto (IR, IV)",
     *         @OA\Schema(type="string", enum={"IR", "IV"})
     *     ),
     *     @OA\Parameter(
     *         name="tipo_persona",
     *         in="query",
     *         description="Tipo de persona (natural, sociedad)",
     *         @OA\Schema(type="string", enum={"natural", "sociedad"})
     *     ),
     *     @OA\Parameter(
     *         name="tipo_regimen",
     *         in="query",
     *         description="Tipo de régimen (rimpe, general)",
     *         @OA\Schema(type="string", enum={"rimpe", "general"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de códigos de retención",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="tipo_impuesto", type="string", example="IR"),
     *                     @OA\Property(property="codigo", type="string", example="303"),
     *                     @OA\Property(property="concepto", type="string"),
     *                     @OA\Property(property="porcentaje", type="number", format="float"),
     *                     @OA\Property(property="tipo_persona", type="string", enum={"natural", "sociedad"}),
     *                     @OA\Property(property="tipo_regimen", type="string", enum={"rimpe", "general"}),
     *                     @OA\Property(property="categoria", type="string")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function codigosRetenciones(Request $request): JsonResponse
    {
        $filters = $request->only(['tipo_impuesto', 'tipo_persona', 'tipo_regimen']);
        $query = CodigoRetencion::activos();
        //$query = CodigoRetencion::estaVigente();
      //  $query = $codigoRetencion->estaVigente();
        if (isset($filters['tipo_impuesto'])) {
            $query->where('tipo_impuesto', $filters['tipo_impuesto']);
        }
        if (isset($filters['tipo_persona'])) {
            $query->where('tipo_persona', $filters['tipo_persona']);
        }
        if (isset($filters['tipo_regimen'])) {
            $query->where('tipo_regimen', $filters['tipo_regimen']);
        }

        $codigos = $query->paginate(100);

        return response()->json([
            'data' => $codigos,
            'message' => 'Códigos de retención obtenidos exitosamente'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/retenciones/crear",
     *     summary="Crear retención",
     *     description="Crea una nueva retención en el sistema",
     *     operationId="crearRetencion",
     *     tags={"Retenciones"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la Retencion",
     *         @OA\JsonContent(
     *             required={"empresa_id", "establecimiento_id", "punto_emision_id", "ambiente",
     *                      "tipo_emision", "fecha_emision", "periodo_fiscal", "sujeto", "detalles"},
     *             @OA\Property(property="empresa_id", type="integer"),
     *             @OA\Property(property="establecimiento_id", type="integer"),
     *             @OA\Property(property="punto_emision_id", type="integer"),
     *             @OA\Property(property="ambiente", type="string", enum={"1", "2"}),
     *             @OA\Property(property="tipo_emision", type="string", enum={"1"}),
     *             @OA\Property(property="fecha_emision", type="string", format="date"),
     *             @OA\Property(property="periodo_fiscal", type="string", example="01/2025"),
     *             @OA\Property(
     *                 property="sujeto",
     *                 type="object",
     *                 @OA\Property(property="tipo_identificacion", type="string", example="04"),
     *                 @OA\Property(property="identificacion", type="string", example="0909090909001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA MACROBILLS"),
     *                 @OA\Property(property="direccion", type="string", example="GUAYAQUIL - ECUADOR"),
     *                 @OA\Property(property="email", type="string" , example="cliente@cliente.com"),
     *                 @OA\Property(property="tipo_sujeto", type="string", enum={"persona_natural", "sociedad"}),
     *                 @OA\Property(property="regimen", type="string", enum={"rimpe", "general"})
     *             ),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"tipo_impuesto", "codigo", "base_imponible", "doc_sustento"},
     *                     @OA\Property(property="tipo_impuesto", type="string", example="IR"),
     *                     @OA\Property(property="codigo", type="string", example="303"),
     *                     @OA\Property(property="base_imponible", type="string", example="100.00"),
     *                     @OA\Property(
     *                         property="doc_sustento",
     *                         type="object",
     *                         @OA\Property(property="codigo", type="string", example="01"),
     *                         @OA\Property(property="numero", type="string", example="001-001-000000012"),
     *                         @OA\Property(property="fecha_emision", type="string", format="date")
     *                     )
     *                 )
     *             ),
     *              @OA\Property(
     *                  property="info_adicional",
     *                  type="array",
     *                  @OA\Items(
     *                      type="object",
     *                      required={"nombre", "valor"},
         *                      @OA\Property(property="nombre", type="string", example="correo"),
     *                      @OA\Property(property="valor", type="string", example="correo@cliente.com")
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response=201,
     *          description="Retencion creada exitosamente",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Retencion creada exitosamente"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(
     *                      property="clave_acceso",
     *                      type="string",
     *                      example="2311202301099287787800110010010000000011234567813"
     *                  ),
     *                  @OA\Property(
     *                      property="numero_retencion",
     *                      type="string",
     *                      example="001-001-000000001"
     *                  ),
     *                  @OA\Property(
     *                      property="estado",
     *                      type="string",
     *                      example="CREADA",
     *                      enum={"CREADA", "FIRMADA", "ENVIADA", "AUTORIZADA", "RECHAZADA", "ANULADA"}
     *                  ),
     *              )
     *          )
     *      ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(RetencionRequest $request): JsonResponse
    {

        try {
            $retencion = $this->retencionService->crear($request->validated());
            $docSRI = new DocumentService();
            $docSRI->guardarDocumentoInicio($request->validated(), Carbon::now(), 'logs_retenciones_recibidas_sin_procesar');
            $docSRI->guardarDocumentoPendiente($retencion->toArray(), $retencion->id, 'retenciones');
            $tiempoPreAPI = microtime(true);
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'retencion' => $retencion->toArray()
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];

            Log::channel('retenciones')->info('data enviada', [
                'data' => $params
            ]);

            $wsd = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl";
            $wdsURL = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc";
            $soapService = new SoapSriService($wsd, $wdsURL);

            // 8. Verificar disponibilidad del servicio SRI
            if (!$soapService->isConnected()) {
                Log::channel('retenciones')->warning('Servicio SRI no disponible', [
                    'status' => $soapService->getStatus(),
                    'clave_acceso' => $retencion->clave_acceso,
                ]);

                $responseData = [
                    'success' => true,
                    'message' => 'Retención creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $retencion->id,
                        'uuid' => $retencion->uuid,
                        'clave_acceso' => $retencion->clave_acceso,
                        'numero_documento' => $retencion->estab . '-' . $retencion->pto_emi . '-' . $retencion->secuencial,
                        'estado' => $retencion->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ];

                Log::channel('retenciones')->info('Retención generada:', $responseData);
                return response()->json($responseData, 201);
            }

            // 9. Enviar nota de crédito al SRI
            $tiempoEnvioSRI = microtime(true);
            $resultadoSoap = $soapService->enviarRetencion($retencion->toArray());
            $tiempoPostAPI = microtime(true);

            Log::channel('retenciones')->info('Nota de crédito generada:', ['data' => $retencion]);

            Log::channel('retenciones')->info('Respuesta recibida del SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_api' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'success' => $resultadoSoap['success'],
                'status' => $resultadoSoap['status'],

            ]);

            // 10. Preparar respuesta
            $response = [
                'success' => true,
                'data' => [
                    'id' => $retencion->id,
                    'uuid' => $retencion->uuid,
                    'clave_acceso' => $retencion->clave_acceso,
                    'numero_documento' => $retencion->estab . '-' . $retencion->ptoemi . '-' . $retencion->secuencial,
                    'estado' => $retencion->estado,
                    'estado_sri' => $resultadoSoap['status']
                ]
            ];

            // 11. Manejar respuesta del SRI
            if (!$resultadoSoap['success']) {
                $response['warning'] = 'Retencion creada pero con errores en el envío al SRI';
                $response['error_sri'] = $resultadoSoap['message'] ?? 'Error no especificado';

                Log::channel('retenciones')->warning('Retencion creada con errores en envío SRI', [
                    'clave_acceso' => $retencion->clave_acceso,
                    'estado_sri' => $resultadoSoap['status'],
                    'error' => $resultadoSoap['message'] ?? 'Error no especificado'
                ]);

                return response()->json($response, 201);
            }

            // 12. Respuesta exitosa
            $response['message'] = 'Retencion creada y enviada exitosamente';

            Log::channel('retenciones')->info('Nota de crédito procesada exitosamente', [
                'clave_acceso' => $retencion->clave_acceso,
            ]);

            return response()->json($response, 201);




            //SECCION ACTUAL
          /*  return response()->json([
                'success' => true,
                'data' => [
                    'retencion' => $retencion
                ],
                'message' => 'Retención creada exitosamente'
            ], 201);
          */
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la retención',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/retenciones/{uuid}",
     *     summary="Obtener retención",
     *     description="Obtiene el detalle de una retención específica",
     *     operationId="obtenerRetencion",
     *     tags={"Retenciones"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retención encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="empresa_id", type="integer"),
     *                 @OA\Property(property="establecimiento_id", type="integer"),
     *                 @OA\Property(property="punto_emision_id", type="integer"),
     *                 @OA\Property(property="uuid", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="ambiente", type="string"),
     *                 @OA\Property(property="clave_acceso", type="string"),
     *                 @OA\Property(property="fecha_emision", type="string", format="date"),
     *                 @OA\Property(property="periodo_fiscal", type="string"),
     *                 @OA\Property(property="identificacion_sujeto", type="string"),
     *                 @OA\Property(property="razon_social_sujeto", type="string"),
     *                 @OA\Property(property="total_retenido", type="number", format="float"),
     *                 @OA\Property(
     *                     property="detalles",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="codigo_retencion_id", type="integer"),
     *                         @OA\Property(property="base_imponible", type="number", format="float"),
     *                         @OA\Property(property="porcentaje_retener", type="number", format="float"),
     *                         @OA\Property(property="valor_retenido", type="number", format="float")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="estados",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="estado", type="string"),
     *                         @OA\Property(property="fecha", type="string", format="date-time"),
     *                         @OA\Property(property="usuario", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Retención obtenida exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Retención no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Retención no encontrada")
     *         )
     *     )
     * )
     */
    public function show( $uuid): JsonResponse
    {
        try {
            $retencion = Retencion::with(['detalles', 'estados'])->where('uuid',$uuid)->first();

            return response()->json([
                'success' => true,
                'data' => $retencion,
                'message' => 'Retención obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retención no encontrada'
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/retenciones/{uuid}/anular",
     *     summary="Anular retención",
     *     description="Anula una retención existente",
     *     operationId="anularRetencion",
     *     tags={"Retenciones"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"motivo"},
     *             @OA\Property(
     *                 property="motivo",
     *                 type="string",
     *                 minLength=10,
     *                 example="Anulación solicitada por el contribuyente"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Retención anulada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retención anulada exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Retención no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Retención no encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede anular la retención",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se puede anular la retención")
     *         )
     *     )
     * )
     */
    public function anular(Request $request, $uuid): JsonResponse
    {
        try {
            $retencion = Retencion::where('uuid',$uuid)->first();

            $request->validate([
                'motivo' => 'required|string|min:10'
            ]);

            $this->retencionService->anular($retencion, $request->motivo);

            return response()->json([
                'success' => true,
                'message' => 'Retención anulada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/retenciones/{claveAcceso}/pdf-download",
     *     summary="Descargar PDF",
     *     description="Descarga el PDF de una Retencion",
     *     operationId="descargarPDFRetencion",
     *     tags={"Retenciones"},
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
            $factura = Retencion::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Simplemente retornar la respuesta que viene de obtenerRideFormatos
            return $documentos->obtenerRideFormatos($factura->uuid, $formato);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retención no encontrada',
                'error' => 'No se encontró la factura con la clave de acceso proporcionada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/retenciones/{claveAcceso}/xml",
     *     summary="Descargar XML",
     *     description="Descarga el XML de una Retención",
     *     operationId="descargarXMLRetencion",
     *     tags={"Retenciones"},
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
            $retencion = Retencion::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Llamar al método obtenerDocumento con tipo 'xml'
            return $documentos->obtenerDocumento($retencion->uuid, $formato, 'xml');

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Retención no encontrada',
                'error' => 'No se encontró la retención con la clave de acceso proporcionada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
