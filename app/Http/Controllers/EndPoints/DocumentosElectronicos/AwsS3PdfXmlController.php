<?php

namespace App\Http\Controllers\EndPoints\DocumentosElectronicos;
use App\Http\Controllers\Controller;
use App\Services\SoapSriService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Cache, DB};
use Illuminate\Support\Facades\Storage;
/**
 * @OA\Tag(
 *     name="Reporteria Electronica",
 *     description="Endpoints para la gestión de facturas electrónicas"
 * )
 */
class AwsS3PdfXmlController extends Controller
{
    /**
     * @OA\Get(
     *     path="/facturacion/{claveAcceso}/pdf",
     *     summary="Descargar PDF de la factura",
     *     description="Genera y descarga el PDF de una factura",
     *     operationId="descargarPDF",
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
     *         description="PDF de la factura",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     )
     * )
     */
    public function descargarPDF(string $claveAcceso): JsonResponse|Response
    {
        try {
            $factura = $this->facturacionService->consultarFactura($claveAcceso);
            $pdf = $factura->generarPDF();

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $claveAcceso . '.pdf"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reprocesarDocumento(Request $request): bool
    {
        $datos = $request->all();

        $uuid =$datos['uuid'];
        $tipoDocumento = $datos['tipo_documento'];
        $tabla = $this->getTipoDocumento($tipoDocumento);
        if ($tabla === 'no_existe' || $tabla === 'no_definido') {
            Log::channel('general')->error('Tipo de documento no válido', [
                'uuid' => $uuid,
                'tipoDocumento' => $tipoDocumento
            ]);
            return false;
        }
        $documento = DB::table($tabla)
            ->where('uuid', $uuid)
            ->first();

        if (!$documento) {
            Log::channel('general')->error('Documento no encontrado', [
                'uuid' => $uuid,
                'tabla' => $tabla
            ]);
            return false;
        }
        $fileName = $documento->uuid. '.json';
        $relativePath = 'pending_docs/'.$tabla.'/' . $fileName;
        if (!Storage::disk('public')->exists($relativePath)) {
            Log::channel('general')->error('Archivo JSON no encontrado', [
                'uuid' => $uuid,
                'relativePath' => $relativePath
            ]);
            return false;
        }

        $jsonContent = Storage::disk('public')->get($relativePath);
        $documentoArray = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('general')->error('Error al decodificar JSON', [
                'uuid' => $uuid,
                'error' => json_last_error_msg()
            ]);
            return false;
        }

        $wsd = env('WEB_SERVICES_WSD');
        $wdsURL = env('WEB_SERVICES_URL');
        $soapService = new SoapSriService($wsd,$wdsURL);
        if (!$soapService->isConnected()) {
            Log::channel('general')->warning('Servicio SRI no disponible', [
                'status' => $soapService->getStatus(),
            ]);
        }

        $resultadoSoap = $soapService->reenviarDocumento($this->getNombeEnvio($tipoDocumento), $documentoArray);
        Log::channel('general')->info('Documento reprocesado', [
            'uuid' => $uuid,
            'tipoDocumento' => $tipoDocumento,
            'resultado' => $resultadoSoap
        ]);

        if (isset($resultadoSoap['success']) && $resultadoSoap['success']) {
            // Actualizar el estado del documento si es necesario
            DB::table($tabla)
                ->where('uuid', $uuid)
                ->update(['procesado_macrobillingec' => 1]);

            return true;
        } else {
            // Registrar el error específico del reprocesamiento
            Log::channel('general')->error('Error en reprocesamiento', [
                'uuid' => $uuid,
                'error' => $resultadoSoap['message'] ?? 'Error desconocido',
                'detalles' => $resultadoSoap
            ]);

            return false;
        }
    }

    public function getTipoDocumento(string $tipoDocumento)
    {
        switch ($tipoDocumento) {
            case '01':
                return 'facturas';
            case '02':
                return 'no_existe';
            case '03':
                return 'liquidaciones_compra';
            case '04':
                return 'notas_credito';
            case '05':
                return 'notas_debito';
            case '06':
                return 'guias_remision';
            case '07':
                return 'retenciones';
            case '08':
                return 'proformas';
            default:
                return 'no_definido';
        }
    }
    public function getNombeEnvio(string $tipoDocumento)
    {
        switch ($tipoDocumento) {
            case '01':
                return 'factura';
            case '02':
                return 'no_existe';
            case '03':
                return 'liquidacion';
            case '04':
                return 'notaCredito';
            case '05':
                return 'notas_debito';
            case '06':
                return 'guiaRemision';
            case '07':
                return 'retencion';
            case '08':
                return 'proformas';
            default:
                return 'no_definido';
        }
    }
    public function enviarDocumentoReprocesar(string $tipoDocumento, array $data)
    {
        switch ($tipoDocumento) {
            case '01':

            case '02':
                return 'no_existe';
            case '03':
                return 'liquidaciones_compra';
            case '04':
                return 'notas_credito';
            case '05':
                return 'notas_debito';
            case '06':
                return 'guias_remision';
            case '07':
                return 'retenciones';
            case '08':
                return 'proformas';
            default:
                return 'no_definido';
        }
    }
}
