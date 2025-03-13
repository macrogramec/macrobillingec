<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="RIDE_XML",
 *     description="Endpoints para los documentos Electronicos Autorizados del SRI "
 * )
 */
class DocumentosControllers extends Controller
{
    /**
     * @OA\Get(
     *     path="/ride_xml/getRIDE",
     *     summary="Generación de RIDE",
     *     description="Generación de RIDE",
     *     operationId="generarRIDE",
     *     tags={"RIDE_XML"},
     *     security={{ "bearerAuth": {} }},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Generación de RIDE",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ride Generado Exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ride En proceso de Generación"
     *     )
     * )
     */
    public function obtenerRIDE(string $uuid, Request $request): JsonResponse
    {
        try {
            $rutaDocumento = DB::table('almacenamiento_S3')
                ->select('url_pdf')
                ->where('uuid', $uuid)
                ->first();
            $rutaArchivo = $rutaDocumento->url_pdf;
            // Verificar si existe el archivo
            if (!Storage::disk('s3')->exists($rutaArchivo)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'RIDE EN PROCESO DE GENERACIÓN'
                ], 404);
            }
            // Obtener el contenido del archivo
            $contenidoArchivo = Storage::disk('s3')->get($rutaArchivo);
            // Convertir a base64
            $base64 = base64_encode($contenidoArchivo);
            return response()->json([
                'success' => true,
                'base64' => $base64
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener el archivo: ' . $e->getMessage()
            ], 500);
        }
    }


    public function obtenerRideFormatos(string $uuid, string $formato, string $tipoDocumento = 'pdf')
    {
        try {
            // Determinar qué columna seleccionar según el tipo de documento
            $columna = $tipoDocumento === 'xml' ? 'url_xml' : 'url_pdf';

            // Buscar la ruta del documento en la base de datos
            $rutaDocumento = DB::table('almacenamiento_S3')
                ->select($columna)
                ->where('uuid', $uuid)
                ->first();

            if (!$rutaDocumento) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Documento no encontrado'
                ], 404);
            }

            $rutaArchivo = $rutaDocumento->$columna;

            // Verificar si existe el archivo
            if (!Storage::disk('s3')->exists($rutaArchivo)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'DOCUMENTO EN PROCESO DE GENERACIÓN'
                ], 404);
            }

            // Configurar el tipo de contenido según el tipo de documento
            $contentType = $tipoDocumento === 'xml' ? 'application/xml' : 'application/pdf';

            // Dependiendo del formato solicitado, dar una respuesta diferente
            switch ($formato) {
                case 'base64':
                    // Obtener el contenido y convertir a base64
                    $contenidoArchivo = Storage::disk('s3')->get($rutaArchivo);
                    $base64 = base64_encode($contenidoArchivo);

                    return response()->json([
                        'success' => true,
                        'base64' => $base64
                    ]);

                case 'binario':
                    // Devolver el archivo directamente como descarga
                    $contenidoArchivo = Storage::disk('s3')->get($rutaArchivo);
                    $extension = pathinfo($rutaArchivo, PATHINFO_EXTENSION) ?: $tipoDocumento;
                    $nombreArchivo = "documento_".$uuid.".".$extension;

                    return response($contenidoArchivo)
                        ->header('Content-Type', $contentType)
                        ->header('Content-Disposition', 'inline; filename="'.$nombreArchivo.'"');

                case 'url':
                    // Generar una URL firmada temporal para acceder al archivo
                    $url = Storage::disk('s3')->temporaryUrl(
                        $rutaArchivo,
                        now()->addMinutes(30) // URL válida por 30 minutos
                    );

                    return response()->json([
                        'success' => true,
                        'url' => $url
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'mensaje' => 'Formato no válido'
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
