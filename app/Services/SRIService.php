<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SRIService
{
    protected $baseUrl = 'https://srienlinea.sri.gob.ec';
    protected $cookieJar;

    public function __construct()
    {
        $this->cookieJar = new \GuzzleHttp\Cookie\CookieJar();
    }

    public function consultarRuc(string $ruc)
    {
        try {
            Log::info('Iniciando consulta RUC', ['ruc' => $ruc]);

            // Paso 1: Realizar la solicitud inicial para obtener cookies y tokens
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'cookies' => $this->cookieJar,
                    'allow_redirects' => true,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:119.0) Gecko/20100101 Firefox/119.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Mode' => 'navigate',
                    'Sec-Fetch-Site' => 'none',
                    'Sec-Fetch-User' => '?1',
                ])
                ->get("{$this->baseUrl}/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc");

            Log::info('Primera respuesta obtenida', ['status' => $response->status()]);

            // Validar si la respuesta fue correcta
            if (!$response->successful()) {
                throw new Exception("Error al obtener la página inicial. Código: " . $response->status());
            }

            // Paso 2: Capturar cookies necesarias
            $cookies = $this->cookieJar->toArray();
            Log::info('Cookies obtenidas', ['cookies' => $cookies]);

            // Paso 3: Realizar la consulta del RUC
            $consultaResponse = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'cookies' => $this->cookieJar,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:119.0) Gecko/20100101 Firefox/119.0',
                    'Accept' => 'application/json',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Origin' => $this->baseUrl,
                    'Referer' => "{$this->baseUrl}/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc",
                ])
                ->get("{$this->baseUrl}/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/validarCedulaRuc", [
                    'numeroRuc' => $ruc,
                ]);

            Log::info('Respuesta de validación obtenida', [
                'status' => $consultaResponse->status(),
                'body' => $consultaResponse->body(),
            ]);

            // Validar si la respuesta fue exitosa
            if (!$consultaResponse->successful()) {
                throw new Exception("Error al validar el RUC. Código: " . $consultaResponse->status());
            }

            // Paso 4: Obtener los datos del RUC
            $detalleResponse = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'cookies' => $this->cookieJar,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:119.0) Gecko/20100101 Firefox/119.0',
                    'Accept' => 'application/json',
                    'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Origin' => $this->baseUrl,
                    'Referer' => "{$this->baseUrl}/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/consultaRuc",
                ])
                ->get("{$this->baseUrl}/sri-en-linea/SriRucWeb/ConsultaRuc/Consultas/obtenerDatosRuc", [
                    'numeroRuc' => $ruc,
                ]);

            $data = $detalleResponse->json();

            Log::info('Datos obtenidos del RUC', ['data' => $data]);

            // Validar los datos obtenidos
            if (empty($data)) {
                throw new Exception("No se pudieron obtener los datos del RUC.");
            }

            // Retornar datos procesados
            return [
                'success' => true,
                'data' => [
                    'ruc' => $ruc,
                    'razon_social' => $data['nombreComercial'] ?? $data['razonSocial'] ?? 'NO DISPONIBLE',
                    'estado_contribuyente' => $data['estadoContribuyente'] ?? 'NO DISPONIBLE',
                    'clase_contribuyente' => $data['claseContribuyente'] ?? 'NO DISPONIBLE',
                    'tipo_contribuyente' => $data['tipoContribuyente'] ?? 'NO DISPONIBLE',
                    'obligado_contabilidad' => $data['obligadoContabilidad'] ?? 'NO',
                    'fecha_consulta' => now()->format('Y-m-d H:i:s'),
                ],
            ];
        } catch (Exception $e) {
            Log::error('Error en consulta RUC', [
                'ruc' => $ruc,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cookies' => $this->cookieJar->toArray(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug_info' => [
                    'cookies' => $this->cookieJar->toArray(),
                ],
            ];
        }
    }
}
