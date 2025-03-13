<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\SoapSriService;
use Illuminate\Support\Facades\DB;

class ProcesarDocumentosPendientes extends Command
{
    protected $signature = 'documentos:procesar-pendientes';
    protected $description = 'Procesa documentos pendientes de envío al SRI';

    public function handle()
    {
        $pendingPath = storage_path('app/pending_docs/');
        $wsd = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc?singleWsdl";
        $wdsURL = "http://172.30.2.28:8050/SERV_RECIBI_JSON_APIS.svc";

        Log::info('Iniciando procesamiento de documentos pendientes');

        try {
            // Verificar si el directorio existe
            if (!file_exists($pendingPath)) {
                Log::info('No existe directorio de documentos pendientes');
                return;
            }

            // Obtener todos los archivos JSON
            $files = glob($pendingPath . 'doc_*.json');

            if (empty($files)) {
                Log::info('No hay documentos pendientes para procesar');
                return;
            }

            $soapService = new SoapSriService($wsd, $wdsURL);

            foreach ($files as $file) {
                $this->procesarArchivo($file, $soapService);
            }

        } catch (\Exception $e) {
            Log::error('Error en el procesamiento de documentos: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
        }
    }

    private function procesarArchivo($file, $soapService)
    {
        $filename = basename($file);
        Log::info('Procesando archivo: ' . $filename);

        try {
            // Extraer ID de la factura del nombre del archivo
            preg_match('/doc_(\d+)_/', $filename, $matches);
            $facturaId = $matches[1] ?? null;

            if (!$facturaId) {
                Log::error('No se pudo extraer ID de factura del archivo: ' . $filename);
                return;
            }

            // Leer contenido del archivo
            $jsonContent = file_get_contents($file);
            $facturaData = json_decode($jsonContent, true);

            if (!$facturaData) {
                Log::error('Error decodificando JSON del archivo: ' . $filename);
                return;
            }

            // Verificar conexión con el servicio
            if (!$soapService->isConnected()) {
                Log::warning('Servicio SRI no disponible, se intentará más tarde');
                return;
            }

            // Intentar enviar la factura
            $resultadoSoap = $soapService->enviarFactura($facturaData);

            if ($resultadoSoap['success']) {
                // Actualizar la base de datos
                DB::table('facturas')
                    ->where('id', $facturaId)
                    ->update([
                        'procesado_macrobillingec' => 1,
                        'claveAcceso' => $resultadoSoap['data']['Clave_Acceso'] ?? null
                    ]);

                // Eliminar el archivo
                unlink($file);
                Log::info('Documento procesado exitosamente: ' . $filename);
            } else {
                Log::warning('Error al procesar documento: ' . $filename, [
                    'error' => $resultadoSoap['message'] ?? 'Error desconocido'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error procesando archivo ' . $filename . ': ' . $e->getMessage());
        }
    }
}
