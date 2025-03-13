<?php

namespace App\Http\Controllers\EndPoints\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\EndPoints\Empresa\CreatePuntoEmisionRequest;
use App\Http\Requests\EndPoints\Empresa\UpdatePuntoEmisionRequest;
use App\Http\Requests\EndPoints\Empresa\UpdateSecuencialRequest;
use App\Http\Resources\EndPoints\Empresa\PuntoEmisionResource;
use App\Http\Resources\EndPoints\Empresa\PuntoEmisionCollection;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Services\EndPoints\Empresa\PuntoEmisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Puntos de Emisión",
 *     description="Endpoints para gestión de puntos de emisión de un establecimiento"
 * )
 */
class PuntoEmisionController extends Controller
{
    protected $puntoEmisionService;

    public function __construct(PuntoEmisionService $puntoEmisionService)
    {
        $this->puntoEmisionService = $puntoEmisionService;
    }

    /**
     * @OA\Get(
     *     path="/empresas/{empresa}/establecimientos/{establecimiento}/puntos-emision",
     *     summary="Obtener listado de puntos de emisión",
     *     description="Obtiene el listado de puntos de emisión de un establecimiento",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="codigo",
     *         in="query",
     *         required=false,
     *         description="Filtrar por código",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="tipo_comprobante",
     *         in="query",
     *         required=false,
     *         description="Filtrar por tipo de comprobante",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         required=false,
     *         description="Filtrar por estado",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de puntos de emisión"
     *     )
     * )
     */
    public function index(Request $request, string $empresa, string $establecimiento)
    {
        $establecimiento = Establecimiento::where('empresa_id', $empresa)
            ->where('id', $establecimiento)
            ->firstOrFail();

        $puntosEmision = $this->puntoEmisionService->getAll(
            $establecimiento->id,
            $request->only(['codigo', 'tipo_comprobante', 'estado']),
            $request->input('per_page', 10)
        );

        return new PuntoEmisionCollection($puntosEmision);
    }

    /**
     * @OA\Post(
     *     path="/empresas/{empresa_id}/establecimientos/{establecimiento_id}/puntos-emision",
     *     summary="Crear un nuevo punto de emisión",
     *     description="Crea un nuevo punto de emisión para un establecimiento específico",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa_id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento_id",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del nuevo punto de emisión",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"codigo", "tipo_comprobante", "secuencial_actual", "estado", "ambiente"},
     *             @OA\Property(
     *                 property="codigo",
     *                 type="string",
     *                 example="001",
     *                 description="Código de 3 dígitos del punto de emisión"
     *             ),
     *             @OA\Property(
     *                 property="tipo_comprobante",
     *                 type="string",
     *                 enum={"01", "02", "03", "04", "05", "06", "07"},
     *                 example="01",
     *                 description="01:Factura, 02:Nota Débito, 03:Nota Crédito, 04:Guía Remisión, 05:Comp. Retención"
     *             ),
     *             @OA\Property(
     *                 property="secuencial_actual",
     *                 type="integer",
     *                 example=1,
     *                 description="Número secuencial inicial"
     *             ),
     *             @OA\Property(
     *                 property="estado",
     *                 type="string",
     *                 enum={"activo", "inactivo"},
     *                 example="activo",
     *                 description="Estado del punto de emisión"
     *             ),
     *             @OA\Property(
     *                 property="ambiente",
     *                 type="string",
     *                 enum={"produccion", "pruebas"},
     *                 example="produccion",
     *                 description="Ambiente del punto de emisión"
     *             ),
     *             @OA\Property(
     *                 property="identificador_externo",
     *                 type="string",
     *                 example="POS-001",
     *                 description="Identificador externo para mapeo con otros sistemas",
     *                 nullable=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Punto de emisión creado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="establecimiento_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="tipo_comprobante", type="string", example="01"),
     *                 @OA\Property(property="secuencial_actual", type="integer", example=1),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="identificador_externo", type="string", example="POS-001"),
     *                 @OA\Property(
     *                     property="secuencias",
     *                     type="object",
     *                     @OA\Property(property="factura", type="integer", example=1),
     *                     @OA\Property(property="nota_credito", type="integer", example=1),
     *                     @OA\Property(property="nota_debito", type="integer", example=1),
     *                     @OA\Property(property="guia_remision", type="integer", example=1),
     *                     @OA\Property(property="retencion", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="datetime", example="2024-01-01 00:00:00"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime", example="2024-01-01 00:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Los datos proporcionados no son válidos"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="codigo",
     *                     type="array",
     *                     @OA\Items(type="string", example="El código debe tener exactamente 3 dígitos")
     *                 ),
     *                 @OA\Property(
     *                     property="tipo_comprobante",
     *                     type="array",
     *                     @OA\Items(type="string", example="El tipo de comprobante seleccionado no es válido")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No tiene permisos para crear puntos de emisión"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Establecimiento no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Establecimiento no encontrado"
     *             )
     *         )
     *     )
     * )
     */
    /*
    public function store(CreatePuntoEmisionRequest $request, $empresa, Establecimiento $establecimiento)
    {
        if ($establecimiento->empresa_id != $empresa) {
            return response()->json(['message' => 'El establecimiento no pertenece a la empresa'], 404);
        }

        $data = $request->validated();
        $data['establecimiento_id'] = $establecimiento->id;
        $data['uuid'] = (string) Str::uuid();
        $puntoEmision = $this->puntoEmisionService->create($data);
        return new PuntoEmisionResource($puntoEmision);
    }
    */
    public function store(CreatePuntoEmisionRequest $request, $empresa, Establecimiento $establecimiento)
    {
        try {
            if ($establecimiento->empresa_id != $empresa) {
                return response()->json([
                    'success' => false,
                    'message' => 'El establecimiento no pertenece a la empresa'
                ], 404);
            }

            $data = $request->validated();
            $data['establecimiento_id'] = $establecimiento->id;
            $data['uuid'] = (string) Str::uuid();

            $puntoEmision = $this->puntoEmisionService->create($data);
            Log::info('Punto de emisión creado', ['punto_emision' => $puntoEmision]);
            
            /*return response()->json([
                'success' => true,
                'message' => 'Punto de emisión creado exitosamente',
                'data' => new PuntoEmisionResource($puntoEmision)
            ], 201);
            */
            return new PuntoEmisionResource($puntoEmision);

        } catch (\Exception $e) {
            $statusCode = $e->getCode() == 409 ? 409 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [
                    'codigo' => [$e->getCode() == 409 ?
                        'Ya existe un punto de emisión con este código y tipo de comprobante' :
                        'Error al crear el punto de emisión']
                ]
            ], $statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/empresas/{empresa}/establecimientos/{establecimiento}/puntos-emision/{punto_emision}",
     *     summary="Obtener información de un punto de emisión específico",
     *     description="Retorna los detalles de un punto de emisión",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="punto_emision",
     *         in="path",
     *         required=true,
     *         description="ID del punto de emisión",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del punto de emisión",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(string $empresa, string $establecimiento, string $punto_emision)
    {
        $establecimiento = Establecimiento::where('empresa_id', $empresa)
            ->where('id', $establecimiento)
            ->firstOrFail();

        $puntoEmision = PuntoEmision::where('establecimiento_id', $establecimiento->id)
            ->where('id', $punto_emision)
            ->firstOrFail();

        return new PuntoEmisionResource($puntoEmision);
    }

    /**
     * @OA\Put(
     *     path="/empresas/{empresa}/establecimientos/{establecimiento}/puntos-emision/{punto_emision}/secuencial",
     *     summary="Actualizar secuencial de un punto de emisión",
     *     description="Actualiza el número secuencial y registra el cambio en el historial",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="punto_emision",
     *         in="path",
     *         required=true,
     *         description="ID del punto de emisión",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"secuencial_actual", "motivo"},
     *             @OA\Property(
     *                 property="secuencial_actual",
     *                 type="integer",
     *                 example=100,
     *                 description="Nuevo número secuencial"
     *             ),
     *             @OA\Property(
     *                 property="motivo",
     *                 type="string",
     *                 example="Actualización por cambio de sistema",
     *                 description="Motivo del cambio de secuencial"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Secuencial actualizado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="establecimiento_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="tipo_comprobante", type="string", example="01"),
     *                 @OA\Property(property="secuencial_actual", type="integer", example=100),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="identificador_externo", type="string", example="POS-001")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Punto de emisión o establecimiento no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No se encontró el recurso solicitado"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Los datos proporcionados no son válidos"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="secuencial_actual",
     *                     type="array",
     *                     @OA\Items(type="string", example="El secuencial debe ser mayor a 0")
     *                 ),
     *                 @OA\Property(
     *                     property="motivo",
     *                     type="array",
     *                     @OA\Items(type="string", example="El motivo del cambio es requerido")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function updateSecuencial(
        UpdateSecuencialRequest $request,
        string $empresa,
        string $establecimiento,
        string $punto_emision
    ) {
        $establecimiento = Establecimiento::where('empresa_id', $empresa)
            ->where('id', $establecimiento)
            ->firstOrFail();
        $puntoEmision = PuntoEmision::findOrFail($punto_emision);

        if ($puntoEmision->establecimiento_id !== $establecimiento->id) {
            return response()->json(['message' => 'El punto de emisión no pertenece al establecimiento'], 404);
        }

        $data = $request->validated();
        $puntoEmision = $this->puntoEmisionService->updateSecuencial(
            $puntoEmision,
            $data['secuencial_actual'],
            $data['motivo']
        );

        return new PuntoEmisionResource($puntoEmision);
    }
    /**
     * @OA\Put(
     *     path="/empresas/{empresa}/establecimientos/{establecimiento}/puntos-emision/{punto_emision}",
     *     summary="Actualizar un punto de emisión",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="punto_emision",
     *         in="path",
     *         required=true,
     *         description="ID del punto de emisión",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="estado",
     *                 type="string",
     *                 enum={"activo", "inactivo"},
     *                 example="activo"
     *             ),
     *             @OA\Property(
     *                 property="ambiente",
     *                 type="string",
     *                 enum={"produccion", "pruebas"},
     *                 example="produccion"
     *             ),
     *             @OA\Property(
     *                 property="identificador_externo",
     *                 type="string",
     *                 example="POS-001"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Punto de emisión actualizado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="establecimiento_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="tipo_comprobante", type="string", example="01"),
     *                 @OA\Property(property="secuencial_actual", type="integer", example=1),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="identificador_externo", type="string", example="POS-001")
     *             )
     *         )
     *     )
     * )
     */
    public function update(
        UpdatePuntoEmisionRequest $request,
        string $empresa,
        string $establecimiento,
        string $punto_emision
    ) {
        $establecimiento = Establecimiento::where('empresa_id', $empresa)
            ->where('id', $establecimiento)
            ->firstOrFail();

        $puntoEmision = PuntoEmision::where('establecimiento_id', $establecimiento->id)
            ->where('id', $punto_emision)
            ->firstOrFail();

        $puntoEmision = $this->puntoEmisionService->update($puntoEmision, $request->validated());
        return new PuntoEmisionResource($puntoEmision);
    }

    /**
     * @OA\Delete(
     *     path="/empresas/{empresa}/establecimientos/{establecimiento}/puntos-emision/{punto_emision}",
     *     summary="Eliminar un punto de emisión",
     *     tags={"Puntos de Emisión"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="establecimiento",
     *         in="path",
     *         required=true,
     *         description="ID del establecimiento",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="punto_emision",
     *         in="path",
     *         required=true,
     *         description="ID del punto de emisión",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Punto de emisión eliminado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Punto de emisión eliminado exitosamente"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No se encontró el punto de emisión"
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(
        string $empresa,
        string $establecimiento,
        string $punto_emision
    ) {
        $establecimiento = Establecimiento::where('empresa_id', $empresa)
            ->where('id', $establecimiento)
            ->firstOrFail();

        $puntoEmision = PuntoEmision::where('establecimiento_id', $establecimiento->id)
            ->where('id', $punto_emision)
            ->firstOrFail();

        $this->puntoEmisionService->delete($puntoEmision);
        return response()->json(['message' => 'Punto de emisión eliminado exitosamente']);
    }
}
