<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentService
{
    public function guardarDocumentoPendiente($data, $facturaId, string $tipo_doc)
    {
        try {

            Log::info('Iniciando guardarDocumentoPendiente', [
                $tipo_doc => $facturaId,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);


// Generar nombre único para el archivo
            $fileName = $data['uuid']. '.json';

            // Definir ruta relativa para Storage público
            $relativePath = 'pending_docs/'.$tipo_doc.'/' . $fileName;

            Log::info('Información del archivo a crear', [
                'fileName' => $fileName,
                'relativePath' => $relativePath,
                'disk' => 'public'  // Especificar que usaremos el disco 'public'
            ]);

            // Crear directorio si no existe usando Storage en el disco 'public'
            if (!Storage::disk('public')->exists('pending_docs/'.$tipo_doc)) {
                Storage::disk('public')->makeDirectory('pending_docs/'.$tipo_doc);
                Log::info('Creado directorio: pending_docs/'.$tipo_doc);
            }

            // Intentar guardar el archivo con JSON_PRETTY_PRINT y UTF-8
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($jsonContent === false) {
                Log::error('Error al codificar JSON', [
                    'json_error' => json_last_error_msg()
                ]);
                throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
            }

            // Guardar archivo usando Storage en el disco 'public'
            $success = Storage::disk('public')->put($relativePath, $jsonContent);

            // Obtener ruta real del archivo para verificaciones
            $filePath = Storage::disk('public')->path($relativePath);

            Log::info('Resultado de escritura', [
                'success' => $success ? 'Sí' : 'No',
                'filePath' => $filePath,
                'archivoCreado' => file_exists($filePath) ? 'Sí' : 'No'
            ]);

            if (!$success) {
                Log::error('Error al escribir archivo', [
                    'error' => error_get_last()
                ]);
                throw new Exception('No se pudo escribir el archivo');
            }

            // Verificar archivo creado
            if (file_exists($filePath)) {
                Log::info('Archivo creado exitosamente', [
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'permisos' => decoct(fileperms($filePath) & 0777),
                    'url' => Storage::disk('public')->url($relativePath)
                ]);
            } else {
                Log::warning('Archivo no encontrado después de guardarlo', [
                    'path' => $filePath
                ]);
            }

            try {
                $parsedDate = Carbon::parse($facturaId);
                // Si no se lanza una excepción, es una fecha válida
                Log::info('El valor enviado es una fecha, no un ID');
            } catch (\Exception $e) {
                // Si se lanza una excepción, no es una fecha válida, entonces es un ID
                $updated = DB::table($tipo_doc)
                    ->where('id', $facturaId)
                    ->update(['procesado_macrobillingec' => 0]);

                Log::info('Actualización base de datos', [
                    $tipo_doc => $facturaId,
                    'actualizado' => $updated ? 'Sí' : 'No'
                ]);
            }

            return [
                'success' => true,
                'path' => $filePath,
                'relativePath' => $relativePath,
                'url' => Storage::disk('public')->url($relativePath),
                'disk' => 'public'
            ];

        } catch (Exception $e) {
            Log::error('Error en guardarDocumentoPendiente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                $tipo_doc => $facturaId
            ]);
            throw $e;
        }
    }
    public function guardarDocumentoInicio($data, $facturaId, string $tipo_doc)
    {
        try {

            Log::info('Iniciando guardarDocumentoPendiente', [
                $tipo_doc => $facturaId,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);


// Generar nombre único para el archivo
            $fileName = 'doc_' . $data['empresa_id'].'_'.$data['establecimiento_id'].'.json';

            // Definir ruta relativa para Storage público
            $relativePath = 'pending_docs/'.$tipo_doc.'/' . $fileName;

            Log::info('Información del archivo a crear', [
                'fileName' => $fileName,
                'relativePath' => $relativePath,
                'disk' => 'public'  // Especificar que usaremos el disco 'public'
            ]);

            // Crear directorio si no existe usando Storage en el disco 'public'
            if (!Storage::disk('public')->exists('pending_docs/'.$tipo_doc)) {
                Storage::disk('public')->makeDirectory('pending_docs/'.$tipo_doc);
                Log::info('Creado directorio: pending_docs/'.$tipo_doc);
            }

            // Intentar guardar el archivo con JSON_PRETTY_PRINT y UTF-8
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($jsonContent === false) {
                Log::error('Error al codificar JSON', [
                    'json_error' => json_last_error_msg()
                ]);
                throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
            }

            // Guardar archivo usando Storage en el disco 'public'
            $success = Storage::disk('public')->put($relativePath, $jsonContent);

            // Obtener ruta real del archivo para verificaciones
            $filePath = Storage::disk('public')->path($relativePath);

            Log::info('Resultado de escritura', [
                'success' => $success ? 'Sí' : 'No',
                'filePath' => $filePath,
                'archivoCreado' => file_exists($filePath) ? 'Sí' : 'No'
            ]);

            if (!$success) {
                Log::error('Error al escribir archivo', [
                    'error' => error_get_last()
                ]);
                throw new Exception('No se pudo escribir el archivo');
            }

            // Verificar archivo creado
            if (file_exists($filePath)) {
                Log::info('Archivo creado exitosamente', [
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'permisos' => decoct(fileperms($filePath) & 0777),
                    'url' => Storage::disk('public')->url($relativePath)
                ]);
            } else {
                Log::warning('Archivo no encontrado después de guardarlo', [
                    'path' => $filePath
                ]);
            }

            try {
                $parsedDate = Carbon::parse($facturaId);
                // Si no se lanza una excepción, es una fecha válida
                Log::info('El valor enviado es una fecha, no un ID');
            } catch (\Exception $e) {
                // Si se lanza una excepción, no es una fecha válida, entonces es un ID
                $updated = DB::table($tipo_doc)
                    ->where('id', $facturaId)
                    ->update(['procesado_macrobillingec' => 0]);

                Log::info('Actualización base de datos', [
                    $tipo_doc => $facturaId,
                    'actualizado' => $updated ? 'Sí' : 'No'
                ]);
            }

            return [
                'success' => true,
                'path' => $filePath,
                'relativePath' => $relativePath,
                'url' => Storage::disk('public')->url($relativePath),
                'disk' => 'public'
            ];

        } catch (Exception $e) {
            Log::error('Error en guardarDocumentoPendiente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                $tipo_doc => $facturaId
            ]);
            throw $e;
        }
    }
}
