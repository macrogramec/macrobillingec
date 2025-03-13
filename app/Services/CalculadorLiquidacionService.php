<?php

namespace App\Services;

use App\Exceptions\LiquidacionCompraException;
use Illuminate\Support\Facades\Cache;

class CalculadorLiquidacionService
{
    protected string $version;

    // Cache de configuraciones
    protected array $decimalesPermitidos = [
        '1.0.0' => [
            'cantidad' => 2,
            'precio_unitario' => 2,
            'base_imponible' => 2,
            'valor_impuesto' => 2
        ],
        '1.1.0' => [
            'cantidad' => 6,
            'precio_unitario' => 6,
            'base_imponible' => 2,
            'valor_impuesto' => 2
        ]
    ];

    protected array $configuracionImpuestos;

    public function __construct()
    {
        $this->configuracionImpuestos = Cache::remember('config_impuestos_liquidacion', 3600, function() {
            return [
                'NORMAL' => ['iva' => 15], // Tarifa general 2024
                'MEDICINAS' => ['iva' => 0],
                'CANASTA_BASICA' => ['iva' => 0],
                'TURISMO' => ['iva' => 8],
                'CONSTRUCCION' => ['iva' => 5],
                'EXPORTACION' => ['iva' => 0]
            ];
        });
    }

    /**
     * Calcula los totales de una liquidación
     */
    public function calcularTotales(array $detalles, string $version = '1.1.0'): array
    {
        $this->version = $version;

        $totales = [
            'total_sin_impuestos' => 0,
            'total_descuento' => 0,
            'total_ice' => 0,
            'total_iva' => 0,
            'total_irbpnr' => 0,
            'total_sin_impuestos_sin_ice' => 0,
            'total_impuestos' => 0,
            'importe_total' => 0
        ];

        // Procesar todos los detalles en una sola iteración
        foreach ($detalles as $detalle) {
            $subtotalDetalle = $this->calcularSubtotalDetalle($detalle);
            $descuentoDetalle = $detalle['descuento'] ?? 0;
            $baseImponible = $subtotalDetalle - $descuentoDetalle;

            // Actualizar totales generales
            $totales['total_sin_impuestos'] += $baseImponible;
            $totales['total_descuento'] += $descuentoDetalle;

            // Procesar impuestos
            foreach ($detalle['impuestos'] as $impuesto) {
                $valorImpuesto = $this->calcularValorImpuesto($impuesto, $baseImponible);

                switch ($impuesto['codigo']) {
                    case '2': // IVA
                        $totales['total_iva'] += $valorImpuesto;
                        break;
                    case '3': // ICE
                        $totales['total_ice'] += $valorImpuesto;
                        break;
                    case '5': // IRBPNR
                        $totales['total_irbpnr'] += $valorImpuesto;
                        break;
                }

                $totales['total_impuestos'] += $valorImpuesto;
            }
        }

        // Calcular totales finales
        $totales['total_sin_impuestos_sin_ice'] = $totales['total_sin_impuestos'] - $totales['total_ice'];
        $totales['importe_total'] = $totales['total_sin_impuestos'] + $totales['total_impuestos'];

        // Redondear todos los valores según la versión
        return $this->redondearTotales($totales);
    }

    /**
     * Calcula el subtotal de un detalle
     */
    protected function calcularSubtotalDetalle(array $detalle): float
    {
        $cantidad = $this->redondear($detalle['cantidad'], $this->decimalesPermitidos[$this->version]['cantidad']);
        $precioUnitario = $this->redondear($detalle['precio_unitario'], $this->decimalesPermitidos[$this->version]['precio_unitario']);

        return $cantidad * $precioUnitario;
    }

    /**
     * Calcula el valor del impuesto
     */
    protected function calcularValorImpuesto(array $impuesto, float $baseImponible): float
    {
        // Si es un valor específico (como en ICE)
        if (isset($impuesto['valor_especifico'])) {
            return $this->redondear($impuesto['valor_especifico'], 2);
        }

        // Cálculo por porcentaje
        $valor = ($baseImponible * $impuesto['tarifa']) / 100;
        return $this->redondear($valor, 2);
    }

    /**
     * Calcula impuestos específicos por tipo de producto
     */
    public function calcularImpuestosEspecificos(string $tipoProducto, float $baseImponible): array
    {
        if (!isset($this->configuracionImpuestos[$tipoProducto])) {
            throw new LiquidacionCompraException("Tipo de producto no válido: {$tipoProducto}");
        }

        $config = $this->configuracionImpuestos[$tipoProducto];
        $impuestos = [];

        // Calcular IVA según configuración
        if (isset($config['iva'])) {
            $impuestos[] = [
                'codigo' => '2',
                'codigo_porcentaje' => $this->obtenerCodigoPorcentajeIVA($config['iva']),
                'tarifa' => $config['iva'],
                'base_imponible' => $this->redondear($baseImponible, 2),
                'valor' => $this->redondear(($baseImponible * $config['iva']) / 100, 2)
            ];
        }

        // Agregar otros impuestos si aplican
        if (isset($config['ice'])) {
            $impuestos[] = $this->calcularICE($baseImponible, $config['ice']);
        }

        return $impuestos;
    }

    /**
     * Calcula el ICE para productos específicos
     */
    protected function calcularICE(float $baseImponible, array $configICE): array
    {
        $valor = isset($configICE['porcentaje'])
            ? ($baseImponible * $configICE['porcentaje']) / 100
            : $configICE['valor_especifico'];

        return [
            'codigo' => '3',
            'codigo_porcentaje' => $configICE['codigo'],
            'tarifa' => $configICE['porcentaje'] ?? 0,
            'base_imponible' => $this->redondear($baseImponible, 2),
            'valor' => $this->redondear($valor, 2),
            'valor_especifico' => $configICE['valor_especifico'] ?? null
        ];
    }

    /**
     * Obtiene el código de porcentaje IVA según tarifa
     */
    protected function obtenerCodigoPorcentajeIVA(float $porcentaje): string
    {
        return Cache::remember("codigo_iva_{$porcentaje}", 3600, function() use ($porcentaje) {
            $codigos = [
                0 => '0',
                15 => '4', // Nuevo código para 15% (2024)
                8 => '8',  // Turismo
                5 => '9'   // Construcción
            ];

            return $codigos[$porcentaje] ?? '4';
        });
    }

    /**
     * Redondea un valor al número de decimales especificado
     */
    protected function redondear(float $valor, int $decimales): float
    {
        return round($valor, $decimales, PHP_ROUND_HALF_UP);
    }

    /**
     * Redondea todos los totales
     */
    protected function redondearTotales(array $totales): array
    {
        foreach ($totales as $key => $value) {
            $totales[$key] = $this->redondear($value, 2);
        }

        return $totales;
    }
}
