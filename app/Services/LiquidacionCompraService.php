<?php

namespace App\Services;

use App\Models\{
    LiquidacionCompra,
    LiquidacionCompraDetalle,
    LiquidacionCompraImpuesto,
    LiquidacionCompraRetencion,
    LiquidacionCompraEstado,
    LiquidacionCompraDetalleAdicional,
    CodigoRetencion
};
use App\Exceptions\LiquidacionCompraException;
use Illuminate\Support\{Str, Facades\DB, Facades\Cache};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LiquidacionCompraService
{
    protected ValidadorLiquidacionService $validadorService;
    protected CalculadorLiquidacionService $calculadorService;
    protected ClaveAccesoGenerator $claveAccesoGenerator;
    protected string $version;
    protected int $tiempoCache = 3600;

    public function __construct(
        ValidadorLiquidacionService $validadorService,
        CalculadorLiquidacionService $calculadorService,
        ClaveAccesoGenerator $claveAccesoGenerator
    ) {
        $this->validadorService = $validadorService;
        $this->calculadorService = $calculadorService;
        $this->claveAccesoGenerator = $claveAccesoGenerator;
    }

    /**
     * Procesa una nueva liquidación de compra de manera optimizada
     */
    public function procesarLiquidacion(array $datos): LiquidacionCompra
    {
       // dd($datos);
        return DB::transaction(function () use ($datos) {
            // Establecer versión y validar datos básicos
            $this->version = $datos['version'] ?? '1.1.0';
            $this->validadorService->validarDatosBasicos($datos);

            // Generar secuencial y clave de acceso
            $secuencial = $this->generarSecuencial($datos['punto_emision']['id']);

            $claveAcceso = $this->claveAccesoGenerator->generate([
                'fecha_emision' => $datos['fecha_emision'],
                'tipo_comprobante' => '03',
                'ruc' => $datos['empresa']['ruc'],
                'tipo_ambiente' => $datos['ambiente'],
                'establecimiento' => $datos['establecimiento']['codigo'],
                'punto_emision' => $datos['punto_emision']['codigo'],
                'secuencial' => $secuencial
            ]);

            // Crear liquidación principal
            $liquidacion = LiquidacionCompra::create([
                'empresa_id' => $datos['empresa']['id'],
                'establecimiento_id' => $datos['establecimiento']['id'],
                'punto_emision_id' => $datos['punto_emision']['id'],
                'uuid' => (string) Str::uuid(),
                'estado' => 'CREADA',
                'version' => $this->version,
                'ambiente' => $datos['ambiente'],
                'tipo_emision' => $datos['tipo_emision'],
                'razon_social' => $datos['empresa']['razon_social'],
                'nombre_comercial' => $datos['empresa']['nombre_comercial'],
                'ruc' => $datos['empresa']['ruc'],
                'clave_acceso' => $claveAcceso,
                'cod_doc' => '03',
                'estab' => $datos['establecimiento']['codigo'],
                'pto_emi' => $datos['punto_emision']['codigo'],
                'secuencial' => $secuencial,
                'obligado_contabilidad' =>$datos['empresa']['obligado_contabilidad'],
                'fecha_emision' => $datos['fecha_emision'],
                'dir_matriz' => $datos['empresa']['direccion_matriz'],
                'dir_establecimiento' => $datos['establecimiento']['direccion'],
                'tipo_identificacion_proveedor' => $datos['proveedor']['tipo_identificacion'],
                'identificacion_proveedor' => $datos['proveedor']['identificacion'],
                'razon_social_proveedor' => $datos['proveedor']['razon_social'],
                'direccion_proveedor' => $datos['proveedor']['direccion'],
                'email_proveedor' => $datos['proveedor']['email'],
                'telefono_proveedor' => $datos['proveedor']['telefono'],
                'tipo_proveedor' => $datos['proveedor']['tipo'] ?? null,
            ]);

            // Procesar detalles e impuestos en batch
            $this->procesarDetallesEnBatch($liquidacion, $datos['detalles']);

            // Procesar retenciones si existen
            if (!empty($datos['retenciones'])) {
                $this->procesarRetencionesEnBatch($liquidacion, $datos['retenciones']);
            }

            // Procesar información adicional en batch
            if (!empty($datos['info_adicional'])) {
                $this->procesarInformacionAdicionalEnBatch($liquidacion, $datos['info_adicional']);
            }

            // Calcular totales
            $totales = $this->calculadorService->calcularTotales($datos['detalles'], $this->version);

            $liquidacion->update($totales);

            // Crear estado inicial
            $this->crearEstadoInicial($liquidacion);

            // Actualizar secuencial usando una sola consulta
            DB::table('puntos_emision')
                ->where('id', $datos['punto_emision']['id'])
                ->update(['secuencial_actual' => $secuencial]);
            // Procesar detalles e impuestos en batch
            $this->procesarFormasAdicionales($liquidacion, $datos['formas_pago']);
            // Refrescar modelo con relaciones
            return $liquidacion->fresh([
                'detalles.impuestos',
                'retenciones',
                'estados',
                'detallesAdicionales',
                'pagos'
            ]);
        });
    }

    /**
     * Procesa los detalles e impuestos en batch
     */
   /*
    protected function procesarDetallesEnBatch(LiquidacionCompra $liquidacion, array $detalles): void
    {
        $detallesInsert = [];
        $impuestosInsert = [];
        $timestamp = now();
        foreach ($detalles as $index => $detalle) {


            $detallesInsert[] = [
                'liquidacion_compra_id' => $liquidacion->id,
                'linea' => $index + 1,
                'codigo_principal' => $detalle['codigo_principal'],
                'codigo_auxiliar' => $detalle['codigo_auxiliar'] ?? null,
                'descripcion' => $detalle['descripcion'],
                'cantidad' => $this->formatearDecimales($detalle['cantidad']),
                'precio_unitario' => $this->formatearDecimales($detalle['precio_unitario']),
                'descuento' => $detalle['descuento'] ?? 0,
                'precio_total_sin_impuesto' => ($detalle['cantidad'] * $detalle['precio_unitario']) - ($detalle['descuento'] ?? 0),
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];

            foreach ($detalle['impuestos'] as $impuesto) {
                $impuestosInsert[] = [
                    'liquidacion_compra_detalle_id' => $detalle['id'],
                    'codigo' => $impuesto['codigo'],
                    'codigo_porcentaje' => $impuesto['codigo_porcentaje'],
                    'tarifa' => $impuesto['tarifa'],
                    'base_imponible' => $impuesto['base_imponible'],
                    'valor' => $impuesto['valor'],
                    'version' => $this->version,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }
        }

        // Insertar en batch
        DB::table('liquidacion_compra_detalles')->insert($detallesInsert);
        DB::table('liquidacion_compra_impuestos')->insert($impuestosInsert);
    }
    */
    protected function procesarFormasAdicionales(LiquidacionCompra $liquidacionCompra, array $formas):void
    {
        $formasData = [];
        $timestamp = now();
        foreach ($formas as $index => $forma) {
            $formasData[] = [
                'liquidacion_id' => $liquidacionCompra->id,
                'formaPago' => $forma['forma_pago'],
                'total' => $forma['total'],
                'plazo' => $forma['plazo'],
                'unidadTiempo' => $forma['unidad_tiempo'],
                'institucionFinanciera' => 0,
                'numeroCuenta' => 0,
                'propietarioTarjeta' => 0,
                'version' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::table('liquidacion_pagos')
            ->insert($formasData);

    }
    protected function procesarDetallesEnBatch(LiquidacionCompra $liquidacion, array $detalles): void
    {
        $detallesData = [];
        $timestamp = now();

        foreach ($detalles as $index => $detalle) {
            $detalleData = [
                'linea' => $index + 1,
                'codigo_principal' => $detalle['codigo_principal'],
                'codigo_auxiliar' => $detalle['codigo_auxiliar'] ?? null,
                'descripcion' => $detalle['descripcion'],
                'cantidad' => $this->formatearDecimales($detalle['cantidad']),
                'precio_unitario' => $this->formatearDecimales($detalle['precio_unitario']),
                'descuento' => $detalle['descuento'] ?? 0,
                'precio_total_sin_impuesto' => ($detalle['cantidad'] * $detalle['precio_unitario']) - ($detalle['descuento'] ?? 0),
                'impuestos' => [], // Aquí se añadirán los impuestos del detalle
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];

            foreach ($detalle['impuestos'] as $impuesto) {
                $detalleData['impuestos'][] = [
                    'codigo' => $impuesto['codigo'],
                    'codigo_porcentaje' => $impuesto['codigo_porcentaje'],
                    'tarifa' => $impuesto['tarifa'],
                    'base_imponible' => $impuesto['base_imponible'],
                    'valor' => $impuesto['valor'],
                    'version' => $this->version,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }

            $detallesData[] = $detalleData;
        }

        // Generar el JSON
        $jsonData = [
            'liquidacion_compra_id' => $liquidacion->id,
            'detalles' => $detallesData,
            'fecha_procesamiento' => $timestamp->toDateTimeString()
        ];

        // Convertir a JSON
        $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);

        // Llamar al SP pasando el JSON
        DB::statement('CALL API_PROCESAR_DETALLES_LIQUIDACION(?)', [$jsonString]);
    }


    /**
     * Procesa las retenciones en batch
     */
    protected function procesarRetencionesEnBatch(LiquidacionCompra $liquidacion, array $retenciones): void
    {
        $retencionesInsert = [];
        $timestamp = now();

        // Obtener códigos de retención en una sola consulta
        $codigosRetencion = Cache::remember('codigos_retencion', $this->tiempoCache, function () use ($retenciones) {
            return DB::table('codigos_retencion')
                ->whereIn('id', array_column($retenciones, 'codigo_retencion_id'))
                ->where('activo', true)
                ->get()
                ->keyBy('id');
        });

        foreach ($retenciones as $retencion) {
            $codigoRetencion = $codigosRetencion->get($retencion['codigo_retencion_id']);

            if (!$codigoRetencion) {
                throw new LiquidacionCompraException('Código de retención no válido');
            }

            $retencionesInsert[] = [
                'liquidacion_compra_id' => $liquidacion->id,
                'codigo' => $retencion['codigo'],
                'codigo_porcentaje' => $retencion['codigo_porcentaje'],
                'tarifa' => $retencion['tarifa'],
                'base_imponible' => $retencion['base_imponible'],
                'valor_retenido' => $retencion['valor_retenido'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }

        DB::table('liquidacion_compra_retenciones')->insert($retencionesInsert);
    }

    /**
     * Procesa la información adicional en batch
     */
    protected function procesarInformacionAdicionalEnBatch(LiquidacionCompra $liquidacion, array $infoAdicional): void
    {
        $infoAdicionalInsert = [];
        $timestamp = now();
        $ordenBase = 0;

        foreach ($infoAdicional as $info) {
            $infoAdicionalInsert[] = [
                'liquidacion_compra_id' => $liquidacion->id,
                'nombre' => $info['nombre'],
                'valor' => $info['valor'],
                'orden' => $info['orden'] ?? $ordenBase,
                'version' => $this->version,
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
            $ordenBase = max($ordenBase, $info['orden'] ?? $ordenBase) + 1;
        }

        $infoAdicionalInsert[] = [
            'liquidacion_compra_id' => $liquidacion->id,
            'nombre' => 'correo',
            'valor' => $liquidacion->email_proveedor,
            'orden' => $ordenBase,
            'version' => $this->version,
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ];
        DB::table('liquidacion_compra_detalles_adicionales')
            ->insert($infoAdicionalInsert);
    }

    /**
     * Crea el estado inicial del documento
     */
    protected function crearEstadoInicial(LiquidacionCompra $liquidacion): void
    {
        LiquidacionCompraEstado::create([
            'liquidacion_compra_id' => $liquidacion->id,
            'estado_anterior' => null,
            'estado_actual' => 'CREADA',
            'observacion' => 'Creación inicial del documento',
            'usuario_id' => auth()->id()
        ]);
    }

    /**
     * Genera el siguiente secuencial con cache optimizado
     */
    protected function generarSecuencial(int $puntoEmisionId): string
    {
        $siguienteSecuencial = Cache::remember(
            "secuencial_punto_emision_{$puntoEmisionId}",
            1,
            function () use ($puntoEmisionId) {
                return DB::table('puntos_emision')
                        ->where('id', $puntoEmisionId)
                        ->value('secuencial_actual') + 1;
            }
        );

        if ($siguienteSecuencial > 999999999) {
            throw new LiquidacionCompraException(
                'Se ha superado el límite de secuenciales para este punto de emisión'
            );
        }

        return str_pad($siguienteSecuencial, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Formatea decimales según versión
     */
    protected function formatearDecimales(float $valor): float
    {
        $decimales = $this->version === '1.1.0' ? 6 : 2;
        return round($valor, $decimales);
    }

    /**
     * Anula una liquidación de compra
     */
    public function anular(LiquidacionCompra $liquidacion, string $motivo, string $usuario): void
    {
        DB::transaction(function () use ($usuario, $liquidacion, $motivo) {
            if ($liquidacion->estaAutorizada()) {
                throw new LiquidacionCompraException('No se puede anular una liquidación autorizada');
            }
            $estado_anterior = $liquidacion->estado;
            $liquidacion->update(['estado' => 'ANULADA']);

            LiquidacionCompraEstado::create([
                'liquidacion_compra_id' => $liquidacion->id,
                'estado_anterior' => $estado_anterior,
                'estado_actual' => 'ANULADA',
                'motivo_anulacion' => $motivo,
                'anulado' => 1,
                'usuario_proceso' => $usuario,
                'fecha_anulacion' => Carbon::now()
            ]);

        });
    }

    /**
     * Consulta una liquidación por clave de acceso
     */
    public function consultarPorClaveAcceso(string $claveAcceso): LiquidacionCompra
    {
        return Cache::remember(
            "liquidacion_{$claveAcceso}",
            300,
            function () use ($claveAcceso) {
                return LiquidacionCompra::where('clave_acceso', $claveAcceso)
                    ->with([
                        'detalles' => function($query) {
                            $query->with(['impuestos' => function($q) {
                                $q->where('activo', true);
                            }]);
                        },
                        'retenciones',
                        'estados',
                        'detallesAdicionales'
                    ])
                    ->firstOrFail();
            }
        );
    }

    /**
     * Procesa la autorización del SRI
     */
    public function procesarAutorizacion(LiquidacionCompra $liquidacion, array $datosAutorizacion): void
    {
        DB::transaction(function () use ($liquidacion, $datosAutorizacion) {
            if ($liquidacion->estado === 'ANULADA') {
                throw new LiquidacionCompraException('No se puede autorizar una liquidación anulada');
            }

            $liquidacion->update([
                'estado' => 'AUTORIZADA',
                'procesado_sri' => true,
                'fecha_autorizacion' => $datosAutorizacion['fecha_autorizacion'],
                'numero_autorizacion' => $datosAutorizacion['numero_autorizacion'],
                'ambiente_autorizacion' => $datosAutorizacion['ambiente']
            ]);

            LiquidacionCompraEstado::create([
                'liquidacion_compra_id' => $liquidacion->id,
                'estado_anterior' => $liquidacion->estado,
                'estado_actual' => 'AUTORIZADA',
                'observacion' => 'Documento autorizado por el SRI',
                'metadata' => $datosAutorizacion,
                'usuario_id' => auth()->id()
            ]);

// Limpiar cache relacionado
            $this->limpiarCache($liquidacion);
        });
    }

    /**
     * Limpia el caché relacionado con una liquidación
     */
    protected function limpiarCache(LiquidacionCompra $liquidacion): void
    {
        $keysToForget = [
            "liquidacion_{$liquidacion->clave_acceso}",
            "secuencial_punto_emision_{$liquidacion->punto_emision_id}",
            "totales_liquidacion_{$liquidacion->id}"
        ];

        Cache::deleteMultiple($keysToForget);
    }

    /**
     * Genera el archivo XML de la liquidación
     */
    public function generarXML(LiquidacionCompra $liquidacion): string
    {
        return Cache::remember("xml_liquidacion_{$liquidacion->id}", 300, function() use ($liquidacion) {
            // Preparar datos para XML
          //  $datosXML = $this->prepararDatosXML($liquidacion);

            // Generar XML según versión
          //  return $this->generarXMLSegunVersion($datosXML, $liquidacion->version);
        });
    }

    /**
     * Prepara los datos para el XML
     */
    protected function prepararDatosXML(LiquidacionCompra $liquidacion): array
    {
        $datos = [
            'infoTributario' => [
                'ambiente' => $liquidacion->ambiente,
                'tipoEmision' => $liquidacion->tipo_emision,
                'razonSocial' => $liquidacion->razon_social,
                'nombreComercial' => $liquidacion->nombre_comercial,
                'ruc' => $liquidacion->ruc,
                'claveAcceso' => $liquidacion->clave_acceso,
                'codDoc' => $liquidacion->cod_doc,
                'estab' => $liquidacion->estab,
                'ptoEmi' => $liquidacion->pto_emi,
                'secuencial' => $liquidacion->secuencial,
                'dirMatriz' => $liquidacion->dir_matriz
            ],
            'infoLiquidacionCompra' => [
                'fechaEmision' => $liquidacion->fecha_emision->format('d/m/Y'),
                'dirEstablecimiento' => $liquidacion->dir_establecimiento,
                'contribuyenteEspecial' => $liquidacion->contribuyente_especial,
                'obligadoContabilidad' => $liquidacion->obligado_contabilidad,
                'tipoIdentificacionProveedor' => $liquidacion->tipo_identificacion_proveedor,
                'razonSocialProveedor' => $liquidacion->razon_social_proveedor,
                'identificacionProveedor' => $liquidacion->identificacion_proveedor,
                'direccionProveedor' => $liquidacion->direccion_proveedor,
                'totalSinImpuestos' => $liquidacion->total_sin_impuestos,
                'totalDescuento' => $liquidacion->total_descuento
            ],
            'detalles' => []
        ];

        // Agregar detalles e impuestos
        foreach ($liquidacion->detalles as $detalle) {
            $detalleXML = [
                'codigoPrincipal' => $detalle->codigo_principal,
                'codigoAuxiliar' => $detalle->codigo_auxiliar,
                'descripcion' => $detalle->descripcion,
                'cantidad' => number_format($detalle->cantidad, 6, '.', ''),
                'precioUnitario' => number_format($detalle->precio_unitario, 6, '.', ''),
                'descuento' => number_format($detalle->descuento, 2, '.', ''),
                'precioTotalSinImpuesto' => number_format($detalle->precio_total_sin_impuesto, 2, '.', ''),
                'impuestos' => []
            ];

            foreach ($detalle->impuestos as $impuesto) {
                $detalleXML['impuestos'][] = [
                    'codigo' => $impuesto->codigo,
                    'codigoPorcentaje' => $impuesto->codigo_porcentaje,
                    'tarifa' => number_format($impuesto->tarifa, 2, '.', ''),
                    'baseImponible' => number_format($impuesto->base_imponible, 2, '.', ''),
                    'valor' => number_format($impuesto->valor, 2, '.', '')
                ];
            }

            $datos['detalles'][] = $detalleXML;
        }

        // Agregar retenciones si existen
        if ($liquidacion->retenciones->isNotEmpty()) {
            $datos['retenciones'] = [];
            foreach ($liquidacion->retenciones as $retencion) {
                $datos['retenciones'][] = [
                    'codigo' => $retencion->codigo,
                    'codigoPorcentaje' => $retencion->codigo_porcentaje,
                    'tarifa' => number_format($retencion->tarifa, 2, '.', ''),
                    'valor' => number_format($retencion->valor_retenido, 2, '.', '')
                ];
            }
        }

        // Agregar información adicional si existe
        if ($liquidacion->detallesAdicionales->isNotEmpty()) {
            $datos['infoAdicional'] = [];
            foreach ($liquidacion->detallesAdicionales as $detalle) {
                $datos['infoAdicional'][] = [
                    'nombre' => $detalle->nombre,
                    'valor' => $detalle->valor
                ];
            }
        }

        return $datos;
    }

    /**
     * Genera el XML según la versión especificada
     */
    protected function generarXMLSegunVersion(array $datos, string $version): string
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<liquidacionCompra id="comprobante" version="' . $version . '"></liquidacionCompra>'
        );

        // Agregar todos los elementos recursivamente
        $this->arrayToXML($datos, $xml);

        // Formatear el XML
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Convierte un array a XML recursivamente
     */
    protected function arrayToXML(array $data, \SimpleXMLElement &$xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }
}
