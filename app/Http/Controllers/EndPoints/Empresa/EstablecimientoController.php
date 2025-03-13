<?php

namespace App\Http\Controllers\EndPoints\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\EndPoints\Empresa\CreateEstablecimientoRequest;
use App\Http\Requests\EndPoints\Empresa\UpdateEstablecimientoRequest;
use App\Http\Resources\EndPoints\Empresa\EstablecimientoResource;
use App\Http\Resources\EndPoints\Empresa\EstablecimientoCollection;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Services\EndPoints\Empresa\EstablecimientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
/**
 * @OA\Tag(
 *     name="Establecimientos",
 *     description="Endpoints para gestión de establecimientos de una empresa"
 * )
 */
class EstablecimientoController extends Controller
{
    protected $establecimientoService;

    public function __construct(EstablecimientoService $establecimientoService)
    {
        $this->establecimientoService = $establecimientoService;
    }

    /**
     * @OA\Get(
     *     path="/empresas/{empresa_id}/establecimientos",
     *     summary="Obtener listado de establecimientos",
     *     tags={"Establecimientos"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa_id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="codigo",
     *         in="query",
     *         description="Filtrar por código",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="estado",
     *         in="query",
     *         description="Filtrar por estado",
     *         @OA\Schema(type="string", enum={"activo", "inactivo"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de establecimientos obtenida exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="empresa_id", type="integer", example=1),
     *                     @OA\Property(property="codigo", type="string", example="001"),
     *                     @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *                     @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *                     @OA\Property(property="estado", type="string", example="activo"),
     *                     @OA\Property(property="ambiente", type="string", example="produccion"),
     *                     @OA\Property(
     *                         property="correos_establecimiento",
     *                         type="array",
     *                         @OA\Items(type="string", example="sucursal@empresa.com")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object"),
     *             @OA\Property(property="links", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada"
     *     )
     * )
     */
    public function index(Request $request, Empresa $empresa)
    {
        $establecimientos = $this->establecimientoService->getAll(
            $empresa->id,
            $request->only(['codigo', 'estado']),
            $request->input('per_page', 10)
        );
        return new EstablecimientoCollection($establecimientos);
    }

    /**
     * @OA\Post(
     *     path="/empresas/{empresa_id}/establecimientos",
     *     summary="Crear un nuevo establecimiento",
     *     tags={"Establecimientos"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="empresa_id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"codigo", "direccion"},
     *             @OA\Property(property="codigo", type="string", example="001"),
     *             @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *             @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *             @OA\Property(property="estado", type="string", enum={"activo", "inactivo"}, example="activo"),
     *             @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
     *             @OA\Property(
     *                 property="correos_establecimiento",
     *                 type="array",
     *                 @OA\Items(type="string", example="sucursal@empresa.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Establecimiento creado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="empresa_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *                 @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(
     *                     property="correos_establecimiento",
     *                     type="array",
     *                     @OA\Items(type="string", example="sucursal@empresa.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function store(CreateEstablecimientoRequest $request, Empresa $empresa)
    {
        $data = $request->validated();
        $data['empresa_id'] = $empresa->id;
        $data['uuid'] = (string) Str::uuid();
        Log::info('Establecimiento creado', ['establecimiento' => $data]);

        $establecimiento = $this->establecimientoService->create($data);

        return new EstablecimientoResource($establecimiento);
    }

    /**
     * @OA\Get(
     *     path="/empresas/{empresa_id}/establecimientos/{establecimiento_id}",
     *     summary="Obtener un establecimiento específico",
     *     tags={"Establecimientos"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Establecimiento encontrado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="empresa_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *                 @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(
     *                     property="correos_establecimiento",
     *                     type="array",
     *                     @OA\Items(type="string", example="sucursal@empresa.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Establecimiento no encontrado"
     *     )
     * )
     */
    public function show(Empresa $empresa, Establecimiento $establecimiento)
    {
        return new EstablecimientoResource($establecimiento);
    }

    /**
     * @OA\Put(
     *     path="/empresas/{empresa_id}/establecimientos/{establecimiento_id}",
     *     summary="Actualizar un establecimiento",
     *     tags={"Establecimientos"},
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *             @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *             @OA\Property(property="estado", type="string", enum={"activo", "inactivo"}, example="activo"),
     *             @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
     *             @OA\Property(
     *                 property="correos_establecimiento",
     *                 type="array",
     *                 @OA\Items(type="string", example="sucursal@empresa.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Establecimiento actualizado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="empresa_id", type="integer", example=1),
     *                 @OA\Property(property="codigo", type="string", example="001"),
     *                 @OA\Property(property="direccion", type="string", example="Av. Principal 123"),
     *                 @OA\Property(property="nombre_comercial", type="string", example="Sucursal Principal"),
     *                 @OA\Property(property="estado", type="string", example="activo"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(
     *                     property="correos_establecimiento",
     *                     type="array",
     *                     @OA\Items(type="string", example="sucursal@empresa.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Establecimiento no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(UpdateEstablecimientoRequest $request, Empresa $empresa, Establecimiento $establecimiento)
    {

        $establecimiento = $this->establecimientoService->update($establecimiento, $request->validated());
        return new EstablecimientoResource($establecimiento);
    }

    /**
     * @OA\Delete(
     *     path="/empresas/{empresa_id}/establecimientos/{establecimiento_id}",
     *     summary="Eliminar un establecimiento",
     *     tags={"Establecimientos"},
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
     *     @OA\Response(
     *         response=200,
     *         description="Establecimiento eliminado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Establecimiento eliminado exitosamente"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Establecimiento no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar el establecimiento porque tiene puntos de emisión asociados"
     *     )
     * )
     */
    public function destroy(Empresa $empresa, Establecimiento $establecimiento)
    {
        $this->establecimientoService->delete($establecimiento);
        return response()->json(['message' => 'Establecimiento eliminado exitosamente']);
    }
}
