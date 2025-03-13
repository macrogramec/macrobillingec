<?php

namespace App\Services;
use SoapClient;
use Exception;
use Illuminate\Support\Facades\Log;
use stdClass;

class SoapSriService
{
    protected $soapClient;
    protected $wsdlUrl;
    protected $serviceUrl;
    protected $status;

    const STATUS = [
        'CONNECTED' => 'CONNECTED',
        'ERROR' => 'ERROR',
        'TIMEOUT' => 'TIMEOUT',
        'INITIALIZING' => 'INITIALIZING'
    ];
/*
    public function __construct()
    {
        $this->status = self::STATUS['INITIALIZING'];
        $this->wsdlUrl = "http://172.30.2.28:8050/SERVI_GENERAR_XML.svc?singleWsdl";
        $this->serviceUrl = "http://172.30.2.28:8050/SERVI_GENERAR_XML.svc";

        try {
            // Primero verificamos si el servicio está disponible
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 1 // timeout en segundos
                ]
            ]);

            $response = @file_get_contents($this->wsdlUrl, false, $ctx);

            if ($response === false) {
                $this->status = self::STATUS['ERROR'];
                Log::warning('Servicio SOAP no disponible', [
                    'status' => $this->status,
                    'url' => $this->wsdlUrl
                ]);
                return; // No lanzamos excepción, solo actualizamos el estado
            }

            $opts = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_DISK,
                'connection_timeout' => 0.5,
                'location' => $this->serviceUrl,
                'stream_context' => stream_context_create([
                    'http' => [
                        'timeout' => 0.5,
                        'protocol_version' => 1.1,
                        'header' => [
                            'Connection: Keep-Alive',
                            'Content-Type: text/xml; charset=utf-8'
                        ]
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]),
                'soap_version' => SOAP_1_1,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
            ];

            $this->soapClient = new SoapClient($this->wsdlUrl, $opts);
            $this->status = self::STATUS['CONNECTED'];

            Log::info('SOAP Client inicializado', [
                'status' => $this->status,
                'timestamp' => now()->format('Y-m-d H:i:s.u')
            ]);

        } catch (\SoapFault $e) {
            $this->status = self::STATUS['ERROR'];
            Log::error('Error SOAP al inicializar cliente', [
                'status' => $this->status,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];
            Log::error('Error general al inicializar SOAP Client', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);
        }
    }
*/
    public function __construct($wsd,$wdsURL)
    {
        $this->status = self::STATUS['INITIALIZING'];
        $this->wsdlUrl = "http://172.30.2.28:8050/SERVI_GENERAR_XML.svc?singleWsdl";
        $this->serviceUrl = "http://172.30.2.28:8050/SERVI_GENERAR_XML.svc";

        $maxRetries = 3;
        $retryDelay = 2; // segundos

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                // Verificar si el servicio está disponible
                $ctx = stream_context_create([
                    'http' => [
                        'timeout' => 1 // timeout en segundos
                    ]
                ]);

                $response = @file_get_contents($this->wsdlUrl, false, $ctx);

                if ($response === false) {
                    throw new Exception("El servicio SOAP no está disponible (intento {$attempt})");
                }

                // Configuración del cliente SOAP
                $opts = [
                    'trace' => true,
                    'exceptions' => true,
                    'cache_wsdl' => WSDL_CACHE_DISK,
                    'connection_timeout' => 2,
                    'location' => $this->serviceUrl,
                    'stream_context' => stream_context_create([
                        'http' => [
                            'timeout' => 2,
                            'protocol_version' => 1.1,
                            'header' => [
                                'Connection: Keep-Alive',
                                'Content-Type: text/xml; charset=utf-8'
                            ]
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ]),
                    'soap_version' => SOAP_1_1,
                    'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
                ];

                $this->soapClient = new SoapClient($this->wsdlUrl, $opts);
                $this->status = self::STATUS['CONNECTED'];

                Log::info('SOAP Client inicializado', [
                    'status' => $this->status,
                    'timestamp' => now()->format('Y-m-d H:i:s.u')
                ]);
                return; // Salimos del bucle si todo funciona

            } catch (Exception $e) {
                Log::warning("Intento fallido de conectar al servicio SOAP (intento {$attempt})", [
                    'error' => $e->getMessage()
                ]);
                if ($attempt === $maxRetries) {
                    $this->status = self::STATUS['ERROR'];
                    Log::error('No se pudo conectar al servicio SOAP después de varios intentos', [
                        'status' => $this->status,
                    ]);
                } else {
                    sleep($retryDelay); // Esperamos antes del siguiente intento
                }
            }
        }
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS['CONNECTED'];
    }

    public function enviarFactura($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'factura' => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);

            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la factura en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }

    public function enviarNotaCredito($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'notaCredito' => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);

            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la factura en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }

    public function enviarFirma($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = new stdClass();
            $params->JSON_VALIDADOR_FIRMA = json_encode([
                'password' => $datosFactura['password'],
                'firma_base_64' => $datosFactura['firma'],
                'id_empresa' => $datosFactura['empresa_id'],
                'condicion' => $datosFactura['opcion']
            ], JSON_UNESCAPED_UNICODE);
          //  dd($params->JSON_RECIBIDO_APIS);
            $response = $this->soapClient->SERV_VALIDADOR_FIRMA_ELECT($params);

            if (isset($response->SERV_VALIDADOR_FIRMA_ELECTResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => ($response->SERV_VALIDADOR_FIRMA_ELECTResult)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERV_VALIDADOR_FIRMA_ELECTResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la firma en el SERVICIO',
                'error' => $e->getMessage()
            ];
        }
    }

    public function enviarLogo($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = new stdClass();
            $params->JSON_LOGO_EMPRESA = json_encode([
                'logo_empresa' => $datosFactura['logo'],
                'id_empresa' => $datosFactura['empresa_id'],
                'condicion' => $datosFactura['opcion']
            ], JSON_UNESCAPED_UNICODE);
            //  dd($params->JSON_RECIBIDO_APIS);
            $response = $this->soapClient->SERV_LOGO_EMPRESA_MACROBILLI($params);

            if (isset($response->SERV_LOGO_EMPRESA_MACROBILLIResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => ($response->SERV_LOGO_EMPRESA_MACROBILLIResult)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERV_LOGO_EMPRESA_MACROBILLIResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la firma en el SERVICIO',
                'error' => $e->getMessage()
            ];
        }
    }

    public function enviarRetencion($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'retencion' => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);

            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la retención en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }
    public function enviarLiquidacion($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'liquidacion' => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);
            Log::info('respuesta enviada', [
                'data' => $response
            ]);
            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la retención en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }
    public function enviarGuiaRemision($datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        'guiaRemision' => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);

            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                return [
                    'success' => true,
                    'status' => $this->status,
                    'data' => json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true)
                ];
            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la retención en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }
    public function reenviarDocumento(string $documento, $datosFactura): array
    {
        if (!$this->isConnected() || !$this->soapClient) {
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'No hay conexión con el servicio SRI'
            ];
        }

        try {
            $params = [
                'JSON_RECIBIDO_APIS' => json_encode([
                    'data' => [
                        $documento => $datosFactura
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ];
            Log::info('data enviada', [
                'data' => $params
            ]);
            $response = $this->soapClient->SERVI_GENERA_ARCHI_RECIBI_JSON($params);

            if (isset($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult)) {
                $respuesta = json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true);
                if($respuesta['Status']){
                    return [
                        'success' => true,
                        'status' => $this->status,
                        'data' => $respuesta
                    ];
                }else{
                    return [
                        'success' => false,
                        'status' => $this->status,
                        'data' => $respuesta
                    ];
                }

            }

            $this->status = self::STATUS['ERROR'];
            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Respuesta inválida del servicio SRI',
                'data' => isset($response) ? json_decode($response->SERVI_GENERA_ARCHI_RECIBI_JSONResult, true) : null
            ];

        } catch (\Exception $e) {
            $this->status = self::STATUS['ERROR'];

            Log::error('Error en llamada SOAP', [
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => $this->status,
                'message' => 'Error al procesar la factura en el SRI',
                'error' => $e->getMessage()
            ];
        }
    }
}
