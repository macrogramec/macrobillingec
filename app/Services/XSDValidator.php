<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Exception;

class XSDDownloader
{
    protected $baseUrl = 'https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet';
    protected $storagePath;

    public function __construct()
    {
        $this->storagePath = storage_path('app/xsd/retenciones');
    }

    public function downloadXSD(): void
    {
        // Asegurarse que el directorio existe
        if (!File::exists($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }

        // Lista de archivos XSD necesarios
        $files = [
            'retencion_v2.0.0.xsd',
            'xmldsig-core-schema.xsd',
            'tipos_v2.0.0.xsd'
        ];

        foreach ($files as $file) {
            try {
                $response = Http::get("{$this->baseUrl}/comprobantes-electronicos-xsd/{$file}");

                if ($response->successful()) {
                    File::put("{$this->storagePath}/{$file}", $response->body());
                } else {
                    throw new Exception("Error descargando {$file}: " . $response->status());
                }
            } catch (Exception $e) {
                \Log::error("Error descargando XSD: " . $e->getMessage());
                throw $e;
            }
        }
    }
}

class XSDValidator
{
    protected $xsdPath;
    protected $version;

    public function __construct(string $version = '2.0.0')
    {
        $this->version = $version;
        $this->xsdPath = storage_path("app/xsd/retenciones/retencion_v{$version}.xsd");

        // Verificar si existe el XSD
        if (!file_exists($this->xsdPath)) {
            try {
                $downloader = new XSDDownloader();
                $downloader->downloadXSD();
            } catch (Exception $e) {
                throw new Exception("No se pudo obtener el archivo XSD: " . $e->getMessage());
            }
        }
    }

    public function validate(string $xml): bool
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        if (!$dom->schemaValidate($this->xsdPath)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = trim($error->message);
            }

            throw new Exception("Error de validación XSD: " . implode(', ', $errorMessages));
        }

        return true;
    }
}
