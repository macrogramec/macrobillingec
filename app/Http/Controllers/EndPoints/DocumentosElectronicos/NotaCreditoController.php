<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;

use App\Http\Controllers\Api\DocumentosControllers;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Services\DocumentService;
use App\Services\NotaCreditoService;
use App\Http\Requests\NotaCreditoRequest;
use App\Models\NotaCredito;
use App\Services\SoapSriService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notas de Crédito",
 *     description="Endpoints para gestión de notas de crédito electrónicas"
 * )
 */
class NotaCreditoController extends Controller
{
    protected $notaCreditoService;

    public function __construct(NotaCreditoService $notaCreditoService)
    {
        $this->notaCreditoService = $notaCreditoService;
    }

    /**
     * @OA\Post(
     *     path="/notas-credito/externa",
     *     summary="Crear nota de crédito externa",
     *     description="Crea una nota de crédito para una factura no emitida en el sistema",
     *     operationId="crearNotaCreditoExterna",
     *     tags={"Notas de Crédito"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la nota de crédito",
     *         @OA\JsonContent(
     *             required={"ambiente", "tipo_emision", "fecha_emision", "comprador", "doc_modificado", "detalles"},
     *             @OA\Property(property="empresa_id", type="integer", example=1),
     *             @OA\Property(property="establecimiento_id", type="integer", example=1),
     *             @OA\Property(property="punto_emision_id", type="integer", example=1),
     *             @OA\Property(property="ambiente", type="string", enum={"1", "2"}, example="1", description="1: Pruebas, 2: Producción"),
     *             @OA\Property(property="tipo_emision", type="string", enum={"1"}, example="1", description="1: Normal"),
     *             @OA\Property(property="fecha_emision", type="string", format="date", example="2024-12-23"),
     *             @OA\Property(
     *                 property="comprador",
     *                 type="object",
     *                 required={"tipo_identificacion", "identificacion", "razon_social", "direccion", "email"},
     *                 @OA\Property(property="tipo_identificacion", type="string", enum={"04", "05", "06", "07", "08"}, example="04"),
     *                 @OA\Property(property="identificacion", type="string", example="0992877878001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                 @OA\Property(property="direccion", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="email", type="string", format="email", example="ejemplo@mail.com")
     *             ),
     *             @OA\Property(
     *                 property="doc_modificado",
     *                 type="object",
     *                 required={"tipo_doc", "fecha_emision", "numero", "motivo"},
     *                 @OA\Property(property="tipo_doc", type="string", enum={"01"}, example="01"),
     *                 @OA\Property(property="fecha_emision", type="string", format="date", example="2024-12-20"),
     *                 @OA\Property(property="numero", type="string", example="001-001-000000001"),
     *                 @OA\Property(property="motivo", type="string", example="Devolución de mercadería")
     *             ),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"codigo_principal", "descripcion", "cantidad", "precio_unitario", "impuestos"},
     *                     @OA\Property(property="codigo_principal", type="string", example="001"),
     *                     @OA\Property(property="codigo_auxiliar", type="string", example="AUX001"),
     *                     @OA\Property(property="tipo_producto", type="string", example="NORMAL"),
     *                     @OA\Property(property="descripcion", type="string", example="Producto de prueba"),
     *                     @OA\Property(property="cantidad", type="number", format="float", example=1),
     *                     @OA\Property(property="precio_unitario", type="number", format="float", example=100),
     *                     @OA\Property(property="descuento", type="number", format="float", example=0),
     *                     @OA\Property(
     *                         property="impuestos",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             required={"codigo", "codigo_porcentaje", "base_imponible", "valor"},
     *                             @OA\Property(property="codigo", type="string", example="02"),
     *                             @OA\Property(property="codigo_porcentaje", type="string", example="4"),
     *                             @OA\Property(property="tarifa", type="number", example=15),
     *                             @OA\Property(property="base_imponible", type="number", example=100),
     *                             @OA\Property(property="valor", type="number", example=15)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nota de crédito creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nota de crédito creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clave_acceso", type="string", example="2311202301099287787800110010010000000011234567813"),
     *                 @OA\Property(property="numero", type="string", example="001-001-000000001"),
     *                 @OA\Property(property="estado", type="string", example="CREADA"),
     *                 @OA\Property(property="url_pdf", type="string", format="uri"),
     *                 @OA\Property(property="url_xml", type="string", format="uri")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Error en los datos de entrada"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function crearExterna(NotaCreditoRequest $request): JsonResponse
    {
        try {
            // 1. Iniciar medición de tiempo
            $tiempoInicio = microtime(true);
            $datos = $request->validated();
            $docSRI = new DocumentService();
            $docSRI->guardarDocumentoPendiente($request->validated(), Carbon::now(), 'logs_notas_credito_recibidas_sin_procesar');
            Log::channel('notas_credito')->info('Iniciando creación de nota de crédito', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_inicio' => $tiempoInicio,
                'datos' => $datos
            ]);

            // 2. Obtener y validar la empresa
            $empresa = Empresa::findOrFail($datos['empresa_id']);
            $datos['empresa'] = [
                'id' => $empresa->id,
                'ruc' => $empresa->ruc,
                'razon_social' => $empresa->razon_social,
                'nombre_comercial' => $empresa->nombre_comercial,
                'direccion_matriz' => $empresa->direccion_matriz,
                'obligado_contabilidad' => $empresa->obligado_contabilidad,
                'contribuyente_especial' => $empresa->contribuyente_especial,
            ];

            // 3. Obtener y validar el establecimiento
            $establecimiento = Establecimiento::findOrFail($datos['establecimiento_id']);
            $datos['establecimiento'] = [
                'id' => $establecimiento->id,
                'codigo' => $establecimiento->codigo,
                'direccion' => $establecimiento->direccion
            ];

            // 4. Obtener y validar el punto de emisión
            $puntoEmision = PuntoEmision::where([
                ['establecimiento_id', '=', $datos['establecimiento_id']],
                ['tipo_comprobante', '=', '04']
            ])->firstOrFail();

            $datos['punto_emision'] = [
                'id' => $puntoEmision->id,
                'codigo' => $puntoEmision->codigo
            ];

            $datos['tipo_ambiente'] = $datos['ambiente'];

            // 5. Obtener siguiente secuencial
            $datos['secuencial'] = $this->notaCreditoService->obtenerSiguienteSecuencial($puntoEmision->id);

            // 6. Procesar la nota de crédito
            $tiempoPreProceso = microtime(true);
            Log::channel('notas_credito')->info('Iniciando procesamiento de nota de crédito', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_transcurrido' => round(($tiempoPreProceso - $tiempoInicio) * 1000, 2) . ' ms'
            ]);

            $notaCredito = $this->notaCreditoService->procesarNotaCreditoExterna($datos);

            $docSRI->guardarDocumentoPendiente($notaCredito->toArray(), $notaCredito->id, 'notas_credito');
            // 7. Preparar conexión con el SRI
            $tiempoPreAPI = microtime(true);
            Log::channel('notas_credito')->info('Nota de crédito creada, iniciando envío al SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_transcurrido' => round(($tiempoPreAPI - $tiempoPreProceso) * 1000, 2) . ' ms',
                'clave_acceso' => $notaCredito->claveAcceso
            ]);

            $wsd = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl";
            $wdsURL = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc";
            $soapService = new SoapSriService($wsd, $wdsURL);

            // 8. Verificar disponibilidad del servicio SRI
            if (!$soapService->isConnected()) {
                Log::channel('notas_credito')->warning('Servicio SRI no disponible', [
                    'status' => $soapService->getStatus(),
                    'clave_acceso' => $notaCredito->claveAcceso,
                    'tiempo_transcurrido' => round((microtime(true) - $tiempoInicio) * 1000, 2) . ' ms'
                ]);

                $responseData = [
                    'success' => true,
                    'message' => 'Nota de crédito creada pero pendiente de envío al SRI',
                    'warning' => 'Servicio SRI temporalmente no disponible',
                    'data' => [
                        'id' => $notaCredito->id,
                        'clave_acceso' => $notaCredito->claveAcceso,
                        'numero_documento' => implode('-', [
                            $notaCredito->establecimiento->codigo,
                            $notaCredito->puntoEmision->codigo,
                            $notaCredito->secuencial
                        ]),
                        'estado' => $notaCredito->estado,
                        'estado_sri' => $soapService->getStatus(),
                        'url_pdf' => "#",
                        'url_xml' => "#"
                    ]
                ];

                Log::channel('notas_credito')->info('Nota de crédito generada:', $responseData);
                return response()->json($responseData, 201);
            }

            // 9. Enviar nota de crédito al SRI
            $tiempoEnvioSRI = microtime(true);
            $resultadoSoap = $soapService->enviarNotaCredito($notaCredito->toArray());
            $tiempoPostAPI = microtime(true);

            Log::channel('notas_credito')->info('Nota de crédito generada:', ['data' => $notaCredito]);

            Log::channel('notas_credito')->info('Respuesta recibida del SRI', [
                'timestamp' => now()->format('Y-m-d H:i:s.u'),
                'tiempo_total_proceso' => round(($tiempoPostAPI - $tiempoInicio) * 1000, 2) . ' ms',
                'tiempo_api' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'success' => $resultadoSoap['success'],
                'status' => $resultadoSoap['status'],
                'clave_acceso' => $notaCredito->claveAcceso
            ]);

            // 10. Preparar respuesta
            $response = [
                'success' => true,
                'data' => [
                    'id' => $notaCredito->id,
                    'clave_acceso' => $notaCredito->claveAcceso,
                    'numero_documento' => implode('-', [
                        $notaCredito->establecimiento->codigo,
                        $notaCredito->puntoEmision->codigo,
                        $notaCredito->secuencial
                    ]),
                    'estado' => $notaCredito->estado,
                    'estado_sri' => $resultadoSoap['status']
                ]
            ];

            // 11. Manejar respuesta del SRI
            if (!$resultadoSoap['success']) {
                $response['warning'] = 'Nota de crédito creada pero con errores en el envío al SRI';
                $response['error_sri'] = $resultadoSoap['message'] ?? 'Error no especificado';

                Log::channel('notas_credito')->warning('Nota de crédito creada con errores en envío SRI', [
                    'clave_acceso' => $notaCredito->claveAcceso,
                    'estado_sri' => $resultadoSoap['status'],
                    'error' => $resultadoSoap['message'] ?? 'Error no especificado'
                ]);

                return response()->json($response, 201);
            }

            // 12. Respuesta exitosa
            $response['message'] = 'Nota de crédito creada y enviada exitosamente';

            Log::channel('notas_credito')->info('Nota de crédito procesada exitosamente', [
                'clave_acceso' => $notaCredito->claveAcceso,
                'tiempo_total' => round((microtime(true) - $tiempoInicio) * 1000, 2) . ' ms'
            ]);

            return response()->json($response, 201);

        } catch (\Exception $e) {
            Log::channel('notas_credito')->error('Error al crear nota de crédito externa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated(),
                'tiempo_total' => isset($tiempoInicio) ? round((microtime(true) - $tiempoInicio) * 1000, 2) . ' ms' : 'N/A'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/notas-credito/interna",
     *     summary="Crear nota de crédito interna",
     *     description="Crea una nota de crédito para una factura emitida en el sistema",
     *     operationId="crearNotaCreditoInterna",
     *     tags={"Notas de Crédito"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la nota de crédito",
     *         @OA\JsonContent(
     *             required={"factura_id", "tipo_aplicacion", "detalles"},
     *             @OA\Property(property="factura_id", type="integer", example=1),
     *             @OA\Property(property="tipo_aplicacion", type="string", enum={"TOTAL", "PARCIAL"}, example="PARCIAL"),
     *             @OA\Property(property="motivo_general", type="string", example="Devolución parcial de mercadería"),
     *             @OA\Property(
     *                 property="detalles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"factura_detalle_id", "cantidad_devuelta", "motivo"},
     *                     @OA\Property(property="factura_detalle_id", type="integer", example=1),
     *                     @OA\Property(property="cantidad_devuelta", type="number", format="float", example=1),
     *                     @OA\Property(property="motivo", type="string", example="Producto en mal estado")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nota de crédito creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nota de crédito creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clave_acceso", type="string"),
     *                 @OA\Property(property="numero", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="tipo_aplicacion", type="string"),
     *                 @OA\Property(property="url_pdf", type="string"),
     *                 @OA\Property(property="url_xml", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Error en los datos de entrada"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */

    public function crearInterna(NotaCreditoRequest $request): JsonResponse
    {
        try {
            $notaCredito = $this->notaCreditoService->procesarNotaCreditoInterna($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Nota de crédito creada exitosamente',
                'data' => [
                    'id' => $notaCredito->id,
                    'clave_acceso' => $notaCredito->clave_acceso,
                    'numero' => $notaCredito->numero_completo,
                    'estado' => $notaCredito->estado,
                    'tipo_aplicacion' => $notaCredito->tipo_aplicacion,
                    'url_pdf' => route('notas-credito.pdf', $notaCredito->clave_acceso),
                    'url_xml' => route('notas-credito.xml', $notaCredito->clave_acceso)
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::channel('notas_credito')->error('Error al crear nota de crédito interna', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->validated()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la nota de crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/notas-credito/{claveAcceso}",
     *     summary="Obtener nota de crédito",
     *     description="Obtiene el detalle de una nota de crédito por su clave de acceso",
     *     operationId="obtenerNotaCredito",
     *     tags={"Notas de Crédito"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la nota de crédito",
     *         @OA\Schema(
     *             type="string",
     *             pattern="^[0-9]{49}$",
     *             example="2311202301099287787800110010010000000011234567813"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nota de crédito encontrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="clave_acceso", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="tipo_aplicacion", type="string"),
     *                 @OA\Property(property="detalles", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="estados", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Nota de crédito no encontrada")
     * )
     */
    public function show(string $claveAcceso): JsonResponse
    {
        try {
            $notaCredito = NotaCredito::where('claveAcceso', $claveAcceso)
                ->with([
                    'detalles' => function($query) {
                        $query->with(['impuestos' => function($q) {
                            $q->where('activo', true);
                        }]);
                    },
                    'estados'
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $notaCredito
            ]);

        } catch (\Exception $e) {
            Log::channel('notas_credito')->error('Error al consultar nota de crédito', [
                'claveAcceso' => $claveAcceso,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar la nota de crédito',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    /**
     * @OA\Post(
     *     path="/notas-credito/{claveAcceso}/anular",
     *     summary="Anular nota de credito",
     *     description="Anula una nota de credito existente",
     *     operationId="anularNotaCredito",
     *     tags={"Notas de Crédito"},
     *     security={{"oauth2": {"admin", "user", "desarrollo", "produccion"}}},
     *     @OA\Parameter(
     *         name="claveAcceso",
     *         in="path",
     *         required=true,
     *         description="Clave de acceso de la nota de credito a anular",
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
     *         description="Nota de Credito anulada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede anular la Nota de Credito"
     *     )
     * )
     */
    public function anular(string $claveAcceso, Request $request): JsonResponse
    {
        try {

            $factura = NotaCredito::where('claveAcceso', $claveAcceso)->firstOrFail();

            $request->validate([
                'motivo' => 'required|string|min:10|max:300'
            ]);

            $this->notaCreditoService->anularNotaCredito(
                $factura,
                $request->motivo,
                $request->user()->name
            );

            return response()->json([
                'success' => true,
                'message' => 'Nota de credito anulada correctamente',
                'data' => [
                    'estado' => $factura->estado,
                    'motivo_anulacion' => $request->motivo
                ]
            ]);

        } catch (\Exception $e) {
            Log::channel('notas_credito')->error('Error al anular la nota de credito', [
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
     *     path="/notas-credito/{claveAcceso}/pdf-download",
     *     summary="Descargar PDF",
     *     description="Descarga el PDF de una nota de credito",
     *     operationId="descargarPDFNotaCredito",
     *     tags={"Notas de Crédito"},
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
            $factura = NotaCredito::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Simplemente retornar la respuesta que viene de obtenerRideFormatos
            return $documentos->obtenerRideFormatos($factura->uuid, $formato);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de Credito no encontrada',
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
     *     path="/notas-credito/{claveAcceso}/xml",
     *     summary="Descargar XML",
     *     description="Descarga el XML de una nota de crédito",
     *     operationId="descargarXMLNotaCredito",
     *     tags={"Notas de Crédito"},
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
            $notaCredito = NotaCredito::where('claveAcceso', $claveAcceso)->firstOrFail();
            $formato = $request->query('formato', 'base64');

            // Instancia del controlador de documentos
            $documentos = new DocumentosControllers();

            // Llamar al método obtenerDocumento con tipo 'xml'
            return $documentos->obtenerDocumento($notaCredito->uuid, $formato, 'xml');

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota de Crédito no encontrada',
                'error' => 'No se encontró la nota de crédito con la clave de acceso proporcionada'
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
