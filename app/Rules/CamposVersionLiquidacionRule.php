<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;

class CamposVersionLiquidacionRule implements Rule
{
    protected $version;
    protected $message = '';
    protected $camposRequeridos = [
        '1.0.0' => [
            'infoTributario' => [
                'razonSocial',
                'ruc',
                'estab',
                'ptoEmi',
                'secuencial',
                'dirMatriz'
            ],
            'infoLiquidacionCompra' => [
                'fechaEmision',
                'tipoIdentificacionProveedor',
                'razonSocialProveedor',
                'identificacionProveedor',
                'totalSinImpuestos',
                'totalDescuento'
            ],
            'detalles' => [
                'codigoPrincipal',
                'descripcion',
                'cantidad',  // 2 decimales máximo
                'precioUnitario', // 2 decimales máximo
                'descuento',
                'precioTotalSinImpuesto'
            ]
        ],
        '1.1.0' => [
            'infoTributario' => [
                'razonSocial',
                'ruc',
                'estab',
                'ptoEmi',
                'secuencial',
                'dirMatriz'
            ],
            'infoLiquidacionCompra' => [
                'fechaEmision',
                'tipoIdentificacionProveedor',
                'razonSocialProveedor',
                'identificacionProveedor',
                'totalSinImpuestos',
                'totalDescuento',
                'periodoFiscal' // Campo adicional en 1.1.0
            ],
            'detalles' => [
                'codigoPrincipal',
                'descripcion',
                'cantidad',  // 6 decimales máximo
                'precioUnitario', // 6 decimales máximo
                'descuento',
                'precioTotalSinImpuesto',
                'unidadMedida'  // Campo adicional en 1.1.0
            ]
        ]
    ];

    protected $validacionesAdicionales = [
        '1.0.0' => [
            'cantidad' => [
                'regex' => '/^\d+(\.\d{1,2})?$/',
                'message' => 'La cantidad debe tener máximo 2 decimales en la versión 1.0.0'
            ],
            'precioUnitario' => [
                'regex' => '/^\d+(\.\d{1,2})?$/',
                'message' => 'El precio unitario debe tener máximo 2 decimales en la versión 1.0.0'
            ]
        ],
        '1.1.0' => [
            'cantidad' => [
                'regex' => '/^\d+(\.\d{1,6})?$/',
                'message' => 'La cantidad debe tener máximo 6 decimales en la versión 1.1.0'
            ],
            'precioUnitario' => [
                'regex' => '/^\d+(\.\d{1,6})?$/',
                'message' => 'El precio unitario debe tener máximo 6 decimales en la versión 1.1.0'
            ]
        ]
    ];

    protected $camposOpcionales = [
        '1.0.0' => [
            'infoTributario' => ['nombreComercial'],
            'infoLiquidacionCompra' => [
                'dirEstablecimiento',
                'contribuyenteEspecial',
                'obligadoContabilidad',
                'direccionProveedor'
            ],
            'detalles' => [
                'codigoAuxiliar'
            ]
        ],
        '1.1.0' => [
            'infoTributario' => ['nombreComercial'],
            'infoLiquidacionCompra' => [
                'dirEstablecimiento',
                'contribuyenteEspecial',
                'obligadoContabilidad',
                'direccionProveedor',
                'rise',  // Nuevo en 1.1.0
                'placa'  // Nuevo en 1.1.0
            ],
            'detalles' => [
                'codigoAuxiliar',
                'detallesAdicionales'  // Nuevo en 1.1.0
            ]
        ]
    ];

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function passes($attribute, $value): bool
    {
        // Validar que la versión sea soportada
        if (!isset($this->camposRequeridos[$this->version])) {
            $this->message = "Versión {$this->version} no soportada";
            return false;
        }

        // Validar campos requeridos
        foreach ($this->camposRequeridos[$this->version] as $seccion => $campos) {
            if (!isset($value[$seccion])) {
                $this->message = "Sección {$seccion} requerida en versión {$this->version}";
                return false;
            }

            foreach ($campos as $campo) {
                if (!isset($value[$seccion][$campo]) || empty($value[$seccion][$campo])) {
                    $this->message = "Campo {$campo} en {$seccion} es requerido en versión {$this->version}";
                    return false;
                }
            }
        }

        // Validar formatos de decimales según versión
        if (isset($value['detalles'])) {
            foreach ($value['detalles'] as $detalle) {
                foreach (['cantidad', 'precioUnitario'] as $campo) {
                    if (isset($detalle[$campo])) {
                        $validacion = $this->validacionesAdicionales[$this->version][$campo];
                        if (!preg_match($validacion['regex'], (string)$detalle[$campo])) {
                            $this->message = $validacion['message'];
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function getCamposRequeridos(): array
    {
        return $this->camposRequeridos[$this->version] ?? [];
    }

    public function getCamposOpcionales(): array
    {
        return $this->camposOpcionales[$this->version] ?? [];
    }

    public function getValidacionesDecimales(): array
    {
        return $this->validacionesAdicionales[$this->version] ?? [];
    }
}
