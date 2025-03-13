<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SRIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SRIController extends Controller
{
    protected $sriService;

    public function __construct(SRIService $sriService)
    {
        $this->sriService = $sriService;
    }

    /**
     * @OA\Get(
     *     path="/sri/consultar-ruc",
     *     summary="Obtiene información detallada de un RUC desde el SRI",
     *     description="Consulta la información de un contribuyente usando su número de RUC",
     *     operationId="consultarRuc",
     *     tags={"SRI"},
     *     security={{"oauth2": {"admin", "user"}}},
     *     @OA\Parameter(
     *         name="ruc",
     *         in="query",
     *         description="Número de RUC del contribuyente",
     *         required=true,
     *         @OA\Schema(type="string", pattern="^[0-9]{13}$")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del RUC recuperada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="ruc", type="string", example="0927218487001"),
     *                 @OA\Property(property="razon_social", type="string", example="CONTRIBUYENTE PRUEBA"),
     *                 @OA\Property(property="nombre_comercial", type="string", example="NOMBRE COMERCIAL PRUEBA"),
     *                 @OA\Property(property="estado_contribuyente", type="string", example="ACTIVO"),
     *                 @OA\Property(property="clase_contribuyente", type="string", example="OTRO"),
     *                 @OA\Property(property="tipo_contribuyente", type="string", example="PERSONA NATURAL"),
     *                 @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *                 @OA\Property(property="actividad_economica", type="string", example="ACTIVIDADES DE DESARROLLO"),
     *                 @OA\Property(property="fecha_inicio_actividades", type="string", format="date", example="2010-01-01"),
     *                 @OA\Property(property="fecha_actualizacion", type="string", format="date", example="2023-12-31"),
     *                 @OA\Property(property="fecha_consulta", type="string", format="datetime", example="2024-11-22 21:37:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autorizado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function consultarRuc(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ruc' => 'required|string|size:13|regex:/^[0-9]+$/',
            ], [
                'ruc.required' => 'El RUC es requerido',
                'ruc.size' => 'El RUC debe tener 13 dígitos',
                'ruc.regex' => 'El RUC debe contener solo números'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $resultado = $this->sriService->consultarRuc($request->ruc);

            return response()->json($resultado);

        } catch (\Exception $e) {
            Log::error('Error en controlador SRI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el RUC',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
