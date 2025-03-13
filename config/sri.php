<?php
// config/sri.php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Ambiente SRI
    |--------------------------------------------------------------------------
    |
    | 1: Pruebas
    | 2: Producción
    */
    'ambiente' => env('SRI_AMBIENTE', '1'),

    /*
    |--------------------------------------------------------------------------
    | URLs de Servicios Web del SRI
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'pruebas' => [
            'recepcion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            'validacion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ValidarComprobante?wsdl'
        ],
        'produccion' => [
            'recepcion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            'validacion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/ValidarComprobante?wsdl'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Códigos de Tipos de Documentos
    |--------------------------------------------------------------------------
    */
    'tipos_documentos' => [
        'factura' => '01',
        'nota_credito' => '04',
        'nota_debito' => '05',
        'guia_remision' => '06',
        'comprobante_retencion' => '07',
        'liquidacion_compra' => '03'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de IVA
    |--------------------------------------------------------------------------
    */
    'iva' => [
        'tarifas' => [
            '0' => [
                'codigo' => '0',
                'codigo_sri' => '0',
                'porcentaje' => 0,
                'descripcion' => 'IVA 0%',
                'tipo' => 'TARIFA_0'
            ],
            '2' => [
                'codigo' => '2',
                'codigo_sri' => '2',
                'porcentaje' => 15,
                'descripcion' => 'IVA 15%',
                'tipo' => 'TARIFA_GENERAL'
            ],
            '6' => [
                'codigo' => '6',
                'codigo_sri' => '6',
                'porcentaje' => 0,
                'descripcion' => 'No Objeto de IVA',
                'tipo' => 'NO_OBJETO'
            ],
            '7' => [
                'codigo' => '7',
                'codigo_sri' => '7',
                'porcentaje' => 0,
                'descripcion' => 'Exento de IVA',
                'tipo' => 'EXENTO'
            ]
        ],
        'codigos_porcentaje' => [
            '0' => '0',    // 0%
            '2' => '2',    // 15%
            '6' => '6',    // No Objeto
            '7' => '7',    // Exento
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de ICE
    |--------------------------------------------------------------------------
    */
    'ice' => [
        'codigo' => '3',
        'tarifas' => [
            // Ejemplos de códigos ICE
            '3023' => [
                'descripcion' => 'Bebidas Alcohólicas',
                'porcentaje' => 75
            ],
            '3051' => [
                'descripcion' => 'Vehículos motorizados cuyo PVP superior a USD 70.000',
                'porcentaje' => 35
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de IRBPNR
    |--------------------------------------------------------------------------
    */
    'irbpnr' => [
        'codigo' => '5',
        'tarifas' => [
            '5001' => [
                'descripcion' => 'Botellas plásticas no retornables',
                'valor' => 0.02
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Tiempo de Espera
    |--------------------------------------------------------------------------
    */
    'timeout' => env('SRI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Reintentos
    |--------------------------------------------------------------------------
    */
    'max_reintentos' => env('SRI_MAX_REINTENTOS', 3),
    'tiempo_entre_reintentos' => env('SRI_TIEMPO_ENTRE_REINTENTOS', 5),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Firma Electrónica
    |--------------------------------------------------------------------------
    */
    'firma' => [
        'path' => env('SRI_FIRMA_PATH', storage_path('firma/firma.p12')),
        'clave' => env('SRI_FIRMA_CLAVE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Documentos
    |--------------------------------------------------------------------------
    */
    'version_xml' => '1.0.0',
    'encoding_xml' => 'UTF-8',
    'version_factura' => '1.1.0',
];
