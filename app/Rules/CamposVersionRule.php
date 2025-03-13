<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CamposVersionRule implements Rule
{
    protected $version;
    protected $message = '';
    protected $camposRequeridos = [
        '1.0.0' => [
            'infoTributario' => ['razonSocial', 'ruc', 'estab', 'ptoEmi', 'secuencial'],
            'infoLiquidacionCompra' => ['fechaEmision', 'identificacionProveedor', 'razonSocialProveedor'],
            'detalles' => ['descripcion', 'cantidad', 'precioUnitario', 'precioTotalSinImpuesto']
        ],
        '1.1.0' => [
            'infoTributario' => ['razonSocial', 'ruc', 'estab', 'ptoEmi', 'secuencial', 'dirMatriz'],
            'infoLiquidacionCompra' => ['fechaEmision', 'identificacionProveedor', 'razonSocialProveedor', 'direccionProveedor'],
            'detalles' => ['descripcion', 'cantidad', 'precioUnitario', 'precioTotalSinImpuesto', 'impuestos']
        ],
        '2.0.0' => [
            'infoTributario' => ['razonSocial', 'ruc', 'estab', 'ptoEmi', 'secuencial', 'dirMatriz', 'ambiente'],
            'infoLiquidacionCompra' => [
                'fechaEmision', 'identificacionProveedor', 'razonSocialProveedor',
                'direccionProveedor', 'periodoFiscal', 'obligadoContabilidad'
            ],
            'detalles' => [
                'descripcion', 'cantidad', 'precioUnitario', 'precioTotalSinImpuesto',
                'impuestos'
            ],
            'retenciones' => ['codigo', 'codigoPorcentaje', 'tarifa', 'valor']
        ],
        '2.1.0' => [
            'infoTributario' => [
                'razonSocial', 'ruc', 'estab', 'ptoEmi', 'secuencial', 'dirMatriz',
                'ambiente', 'tipoEmision'
            ],
            'infoLiquidacionCompra' => [
                'fechaEmision', 'identificacionProveedor', 'razonSocialProveedor',
                'direccionProveedor', 'periodoFiscal', 'obligadoContabilidad',
                'tipoProveedor'
            ],
            'detalles' => [
                'descripcion', 'cantidad', 'precioUnitario', 'precioTotalSinImpuesto',
                'impuestos', 'detallesAdicionales'
            ],
            'retenciones' => ['codigo', 'codigoPorcentaje', 'tarifa', 'valor']
        ]
    ];

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function passes($attribute, $value): bool
    {
        if (!isset($this->camposRequeridos[$this->version])) {
            $this->message = 'Versión no soportada.';
            return false;
        }

        $campos = $this->camposRequeridos[$this->version];
        foreach ($campos as $seccion => $camposRequeridos) {
            if (!isset($value[$seccion])) {
                $this->message = "La sección {$seccion} es requerida para la versión {$this->version}.";
                return false;
            }

            foreach ($camposRequeridos as $campo) {
                if (!isset($value[$seccion][$campo]) || empty($value[$seccion][$campo])) {
                    $this->message = "El campo {$campo} en la sección {$seccion} es requerido para la versión {$this->version}.";
                    return false;
                }
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
