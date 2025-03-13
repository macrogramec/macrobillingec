<?php

namespace App\Services;

use App\Exceptions\LiquidacionCompraException;
use App\Rules\CamposVersionLiquidacionRule;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ValidadorLiquidacionService
{
    protected $versionRule;
    protected $catalogos;
    protected $tiempoCache = 3600; // 1 hora

    public function __construct()
    {
        // Cargar catálogos en constructor para reutilizar
        $this->catalogos = $this->cargarCatalogos();
    }

    /**
     * Carga todos los catálogos necesarios
     */
    protected function cargarCatalogos(): array
    {
        return Cache::remember('catalogos_liquidacion', $this->tiempoCache, function() {
            return [
                'tipos_impuesto' => DB::table('tipos_impuestos')
                    ->where('activo', true)
                    ->pluck('codigo_sri')
                    ->toArray(),

                'tarifas_impuesto' => DB::table('tarifas_impuestos')
                    ->where('activo', true)
                    ->get(['codigo_sri', 'tipo_impuesto_codigo', 'porcentaje'])
                    ->keyBy('codigo_sri')
                    ->toArray(),

                'codigos_retencion' => DB::table('codigos_retencion')
                    ->where('activo', true)
                    ->get(['id', 'tipo_persona', 'tipo_regimen', 'porcentaje'])
                    ->keyBy('id')
                    ->toArray()
            ];
        });
    }

    /**
     * Valida los datos básicos de una liquidación
     */
    public function validarDatosBasicos(array $datos): void
    {
        // Validar versión primero
        if (!isset($datos['version']) || !in_array($datos['version'], ['1.0.0', '1.1.0'])) {
            throw new LiquidacionCompraException('Versión del documento no válida o no especificada');
        }

        $this->versionRule = new CamposVersionLiquidacionRule($datos['version']);

        // Validaciones en batch para reducir procesamiento
        $validaciones = [
            'ambiente' => fn() => $this->validarAmbiente($datos['ambiente']),
            'tipo_emision' => fn() => $this->validarTipoEmision($datos['tipo_emision']),
            'fechas' => fn() => $this->validarFechas($datos),
            'proveedor' => fn() => $this->validarProveedor($datos['proveedor'], $datos['version']),
            'detalles' => fn() => $this->validarDetalles($datos['detalles'], $datos['version']),
            'totales' => fn() => $this->validarTotales($datos)
        ];
        $errores = [];
        foreach ($validaciones as $tipo => $validacion) {
            try {
                $validacion();
            } catch (LiquidacionCompraException $e) {
                $errores[$tipo] = $e->getMessage();
            }
        }

        if (!empty($errores)) {
            throw new LiquidacionCompraException(
                "Errores de validación: " . implode(", ", $errores)
            );
        }
    }

    /**
     * Valida el ambiente
     */
    protected function validarAmbiente(string $ambiente): void
    {
        if (!in_array($ambiente, ['1', '2'])) {
            throw new LiquidacionCompraException('El ambiente debe ser 1 (Pruebas) o 2 (Producción)');
        }
    }

    /**
     * Valida el tipo de emisión
     */
    protected function validarTipoEmision(string $tipoEmision): void
    {
        if ($tipoEmision !== '1') {
            throw new LiquidacionCompraException('El tipo de emisión debe ser 1 (Normal)');
        }
    }

    /**
     * Valida las fechas
     */
    protected function validarFechas(array $datos): void
    {
        $fechaEmision = Carbon::parse($datos['fecha_emision']);

        if ($fechaEmision->isAfter(now())) {
            throw new LiquidacionCompraException('La fecha de emisión no puede ser futura');
        }

        // Validar periodo fiscal solo en versión 1.1.0
        if ($datos['version'] === '1.1.0') {
            if (!isset($datos['periodo_fiscal'])) {
                throw new LiquidacionCompraException('El periodo fiscal es requerido en la versión 1.1.0');
            }

            $periodoFiscal = Carbon::createFromFormat('m/Y', $datos['periodo_fiscal']);
            if ($periodoFiscal->format('m/Y') !== $fechaEmision->format('m/Y')) {
                throw new LiquidacionCompraException('El periodo fiscal debe corresponder al mes y año de emisión');
            }
        }
    }

    /**
     * Valida los datos del proveedor
     */
    protected function validarProveedor(array $proveedor, string $version): void
    {
        // Cache de patrones para validaciones
        $patrones = Cache::remember('patrones_validacion', $this->tiempoCache, function() {
            return [
                'ruc' => '/^[0-9]{13}$/',
                'cedula' => '/^[0-9]{10}$/'
            ];
        });

        // Validar tipo de identificación
        if (!in_array($proveedor['tipo_identificacion'], ['04', '05', '06', '07', '08'])) {
            throw new LiquidacionCompraException('Tipo de identificación del proveedor no válido');
        }

        // Validar identificación según tipo
        $identificacion = $proveedor['identificacion'];
        switch ($proveedor['tipo_identificacion']) {
            case '04': // RUC
                if (!preg_match($patrones['ruc'], $identificacion)) {
                    throw new LiquidacionCompraException('RUC del proveedor no válido');
                }
                break;

            case '05': // Cédula
                if (!preg_match($patrones['cedula'], $identificacion)) {
                    throw new LiquidacionCompraException('Cédula del proveedor no válida');
                }
                break;

            case '06': // Pasaporte
                if (strlen($identificacion) < 3 || strlen($identificacion) > 20) {
                    throw new LiquidacionCompraException('Longitud de pasaporte no válida');
                }
                break;

            case '07': // Consumidor Final
                if ($identificacion !== '9999999999999') {
                    throw new LiquidacionCompraException('Identificación de consumidor final debe ser 9999999999999');
                }
                break;
        }

        // Validaciones adicionales versión 1.1.0
        if ($version === '1.1.0') {
            if (!empty($proveedor['rise']) && strlen($proveedor['rise']) > 40) {
                throw new LiquidacionCompraException('El número RISE no puede exceder 40 caracteres');
            }
        }
    }

    /**
     * Valida los detalles de la liquidación
     */
    protected function validarDetalles(array $detalles, string $version): void
    {
        $decimalesPermitidos = $version === '1.1.0' ? 6 : 2;

        foreach ($detalles as $detalle) {
            // Validar estructura básica
            $this->validarEstructuraDetalle($detalle);

            // Validar precisión decimal
            if (!$this->validarPrecisionDecimal($detalle['cantidad'], $decimalesPermitidos) ||
                !$this->validarPrecisionDecimal($detalle['precio_unitario'], $decimalesPermitidos)) {
                throw new LiquidacionCompraException("Los valores numéricos deben tener máximo {$decimalesPermitidos} decimales");
            }

            // Validar impuestos
            $this->validarImpuestosDetalle($detalle, $version);
        }
    }

    /**
     * Valida la estructura básica de un detalle
     */
    protected function validarEstructuraDetalle(array $detalle): void
    {
        $camposRequeridos = ['codigo_principal', 'descripcion', 'cantidad', 'precio_unitario', 'impuestos'];

        foreach ($camposRequeridos as $campo) {
            if (!isset($detalle[$campo])) {
                throw new LiquidacionCompraException("El campo {$campo} es requerido en el detalle");
            }
        }
    }

    /**
     * Valida la precisión decimal de un número
     */
    protected function validarPrecisionDecimal(float $valor, int $decimalesPermitidos): bool
    {
        $partes = explode('.', (string)$valor);
        return !isset($partes[1]) || strlen($partes[1]) <= $decimalesPermitidos;
    }

    /**
     * Valida los impuestos de un detalle
     */
    protected function validarImpuestosDetalle(array $detalle, string $version): void
    {
        foreach ($detalle['impuestos'] as $impuesto) {
            // Validar que el código de impuesto existe
            if (!in_array($impuesto['codigo'], $this->catalogos['tipos_impuesto'])) {
                throw new LiquidacionCompraException('Código de impuesto no válido');
            }

            // Validar que la tarifa existe para el tipo de impuesto
            $tarifaKey = $impuesto['codigo_porcentaje'];
            if (!isset($this->catalogos['tarifas_impuesto'][$tarifaKey])) {
                throw new LiquidacionCompraException('Tarifa de impuesto no válida');
            }

            // Validar cálculo
            $baseImponible = ($detalle['cantidad'] * $detalle['precio_unitario']) - ($detalle['descuento'] ?? 0);
            $valorCalculado = ($baseImponible * $impuesto['tarifa']) / 100;

            if (abs($valorCalculado - $impuesto['valor']) > 0.01) {
                throw new LiquidacionCompraException('El valor del impuesto no coincide con el cálculo esperado');
            }
        }
    }

    /**
     * Valida los totales
     */
    protected function validarTotales(array $datos): void
    {
        // Calcular totales a partir de los detalles
        $totalSinImpuestos = 0;
        $totalDescuento = 0;
        $totalImpuestos = 0;
        $totalConImpuestos = 0;

        foreach ($datos['detalles'] as $detalle) {
            $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
            $descuento = $detalle['descuento'] ?? 0;

            $totalSinImpuestos += $subtotal - $descuento;
            $totalDescuento += $descuento;

            if (isset($detalle['impuestos']) && is_array($detalle['impuestos'])) {
                foreach ($detalle['impuestos'] as $impuesto) {
                    $totalImpuestos += $impuesto['valor'];
                }
            }
        }

        $totalConImpuestos = $totalSinImpuestos + $totalImpuestos;

        // Agregar los totales calculados al array de datos
        $datos['total_sin_impuestos'] = $totalSinImpuestos;
        $datos['total_descuento'] = $totalDescuento;
        $datos['total_impuestos'] = $totalImpuestos;
        $datos['importe_total'] = $totalConImpuestos;

        // Validar que los totales sean números positivos
        if ($totalSinImpuestos < 0) {
            throw new LiquidacionCompraException('El total sin impuestos no puede ser negativo');
        }

        if ($totalDescuento < 0) {
            throw new LiquidacionCompraException('El total de descuentos no puede ser negativo');
        }

        if ($totalImpuestos < 0) {
            throw new LiquidacionCompraException('El total de impuestos no puede ser negativo');
        }

        if ($totalConImpuestos <= 0) {
            throw new LiquidacionCompraException('El importe total debe ser mayor a cero');
        }
    }
}
