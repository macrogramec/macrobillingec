<?php

namespace App\Services;

use App\Models\{Factura, Empresa, Establecimiento, FacturaDetalleAdicional, FacturaEstado, FacturaPago, PuntoEmision};
use App\Exceptions\FacturacionException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\CalculadorImpuestosService;

class FacturacionService
{
    protected $impuestoFacturaService;
    protected $validadorImpuestos;
    protected $claveAccesoGenerator;
    protected $calculadorImpuestos;

    public function __construct(
        ImpuestoFacturaService $impuestoFacturaService,
        ValidadorImpuestosService $validadorImpuestos,
        ClaveAccesoGenerator $claveAccesoGenerator,
        CalculadorImpuestosService $calculadorImpuestos
    ) {
        $this->impuestoFacturaService = $impuestoFacturaService;
        $this->validadorImpuestos = $validadorImpuestos;
        $this->claveAccesoGenerator = $claveAccesoGenerator;
        $this->calculadorImpuestos = $calculadorImpuestos;
    }

    /**
     * Procesar y crear una nueva factura
     */
    /*
    public function procesarFactura(array $datos): Factura
    {

        return DB::transaction(function () use ($datos) {

            // 1. Obtener y validar entidades relacionadas
            /*
            $empresa = Empresa::findOrFail($datos['empresa_id']);
            $establecimiento = Establecimiento::findOrFail($datos['establecimiento_id']);
            $puntoEmision = PuntoEmision::findOrFail($datos['punto_emision_id']);

            $resultados = DB::select('CALL API_OBTENER_DATOS_FACTURACION(?, ?, ?)', [
                $datos['empresa_id'],
                $datos['establecimiento_id'],
                $datos['punto_emision_id']
            ]);

            if (empty($resultados)) {
                throw new ModelNotFoundException("No se encontraron los registros solicitados");
            }

            $resultado = $resultados[0];

            // 2. Generar secuencial
            $secuencial = $this->generarSecuencial($resultado->secuencial_actual);

            // 3. Generar clave de acceso

            $claveAcceso = '';

            // 4. Procesar y validar totales
            $this->validarTotales($datos['detalles'], $datos['formas_pago']);

            // 5. Crear la factura
            $factura = Factura::create([
                'uuid' => Str::uuid(),
                'version' => '1.1.0', // Versión actual del esquema
                'empresa_id' => $resultado->empresa_id,
                'establecimiento_id' => $resultado->establecimiento_id,
                'punto_emision_id' => $resultado->punto_emision_id,
                'secuencial' => $secuencial,
                'claveAcceso' => $claveAcceso,
                'estado' => 'CREADA',
                'ambiente' => $datos['ambiente'],
                'tipoEmision' => '1', // Normal
                'razonSocial'=> $resultado->razon_social,
                'nombreComercial' => $resultado->nombre_comercial,
                'ruc' => $resultado->ruc,
                'fechaEmision' => $datos['fechaEmision'],
                'codDoc' => '01',
                'estab' => $resultado->establecimiento_codigo,
                'ptoEmi' => $resultado->punto_emision_codigo,
                'dirMatriz' => $resultado->direccion_matriz,
                'dirEstablecimiento' => $resultado->establecimiento_direccion,
                'contribuyenteEspecial' => $resultado->contribuyente_especial,
                'obligadoContabilidad' => $resultado->obligado_contabilidad,

                // Datos del comprador
                'identificacionComprador' => $datos['comprador']['identificacion'],
                'tipoIdentificacionComprador' => $datos['comprador']['tipo_identificacion'],
                'razonSocialComprador' => $datos['comprador']['razon_social'],
                'direccionComprador' => $datos['comprador']['direccion'],
                'emailComprador' => $datos['comprador']['email'],

                // Datos adicionales
                'moneda' => 'DOLAR',
                'propina' => $datos['propina'] ?? 0,
                'observacion' => $datos['observacion'] ?? null
            ]);

            // 6. Procesar impuestos y detalles
            $resultadoImpuestos = $this->impuestoFacturaService->procesarImpuestosFactura(
                $factura,
                $datos['detalles']
            );

            // 7. Actualizar totales de la factura
            $factura->update([
                'totalSinImpuestos' => $resultadoImpuestos['totales']['subtotal'],
                'totalDescuento' => $resultadoImpuestos['totales']['descuento'],
                'totalImpuestos' => $resultadoImpuestos['totales']['total_impuesto'],
                'importeTotal' => $resultadoImpuestos['totales']['total']
            ]);
          //  dd($factura->id);
            // 8. Procesar formas de pago
            foreach ($datos['formas_pago'] as $pago) {
                FacturaPago::create([
                    'factura_id' => $factura->id,
                    'formaPago' => $pago['forma_pago'],
                    'total' => $pago['total'],
                    'plazo' => $pago['plazo'] ?? null,
                    'unidadTiempo' => $pago['unidad_tiempo'] ?? null,
                    'institucionFinanciera' => $pago['institucion_financiera'] ?? null,
                    'numeroCuenta' => $pago['numero_cuenta'] ?? null,
                    'numeroTarjeta' => $pago['numero_tarjeta'] ?? null,
                    'propietarioTarjeta' => $pago['propietario_tarjeta'] ?? null,
                    'version' => '1.1.0'
                ]);

            }

            // 9. Procesar detalles adicionales
            $detallesAdicionales = $datos['detalles_adicionales'] ?? $this->generarDetallesAdicionalesDefault($datos);
            foreach ($detallesAdicionales as $detalle) {
                FacturaDetalleAdicional::create([
                    'factura_id' => $factura->id,
                    'nombre' => $detalle['nombre'],
                    'valor' => $detalle['valor'],
                    'version' => '1.1.0',
                    'usuario_creacion' => auth()->id(),
                    'ip_creacion' => request()->ip()
                ]);

            }

            // 10. Registrar estado inicial
            FacturaEstado::create([
                'factura_id' => $factura->id,
                'estado_actual' => 'CREADA',
                //'mensaje' => 'Factura creada exitosamente',
                'fecha' => Carbon::now()
            ]);

            // 11. Actualizar último secuencial usado
            DB::table('puntos_emision')
                ->where('id', $datos['punto_emision_id'])
                ->update(['secuencial_actual' => $secuencial]);

            // 12. Retornar factura creada
            return $factura->fresh([
                'detalles.impuestos',
                'impuestos',
                'pagos',
                'detallesAdicionales',
                'estados'
            ]);
        });
    }
    */

    public function procesarFactura(array $datos): Factura
    {
        // Iniciamos la transacción manualmente
        try {
            $resultados = DB::select('CALL API_OBTENER_DATOS_FACTURACION(?, ?, ?, ?,?)', [
                $datos['empresa_id'],
                $datos['establecimiento_id'],
                $datos['punto_emision_id'],
                $datos['ambiente'],
                '01'
            ]);

            if (empty($resultados)) {
                throw new ModelNotFoundException("No se encontraron los registros solicitados");
            }

            $resultado = $resultados[0];
            $secuencial = $this->generarSecuencial($resultado->secuencial_actual);

            // Procesar y validar totales
            $this->validarTotales($datos['detalles'], $datos['formas_pago']);
            $resultadoCalculo  = $this->calculadorImpuestos->calcularImpuestosFactura($datos['detalles']);


            // Estructurar JSON para el SP
            $jsonData = [
                'empresa_id' => $resultado->empresa_id,
                'establecimiento_id' => $resultado->establecimiento_id,
                'punto_emision_id' => $resultado->punto_emision_id,
                'secuencial' => $secuencial,
                'claveAcceso' => $this->claveAccesoGenerator->generate([
                    'fecha_emision' => $datos['fechaEmision'],
                    'tipo_comprobante' => '01',
                    'ruc' => $resultado->ruc,
                    'tipo_ambiente' => $datos['ambiente'],
                    'establecimiento' => $resultado->establecimiento_codigo,
                    'punto_emision' => $resultado->punto_emision_codigo,
                    'secuencial' => $secuencial
                ]),
                'ambiente' => $datos['ambiente'],
                'razonSocial' => $resultado->razon_social,
                'nombreComercial' => $resultado->nombre_comercial,
                'ruc' => $resultado->ruc,
                'fechaEmision' => $datos['fechaEmision'],
                'estab' => $resultado->establecimiento_codigo,
                'ptoEmi' => $resultado->punto_emision_codigo,
                'dirMatriz' => $resultado->direccion_matriz,
                'dirEstablecimiento' => $resultado->establecimiento_direccion,
                'contribuyenteEspecial' => $resultado->contribuyente_especial,
                'obligadoContabilidad' => $resultado->obligado_contabilidad,

                'comprador' => $datos['comprador'],
                'detalles' => $datos['detalles'],
                'formas_pago' => $datos['formas_pago'],
                'detalles_adicionales' => $datos['detalles_adicionales'] ??
                    $this->generarDetallesAdicionalesDefault($datos),

                'propina' => $datos['propina'] ?? 0,
                'observacion' => $datos['observacion'] ?? null,

                'usuario_id' => auth()->id(),
                'ip_creacion' => request()->ip()
            ];
            $jsonData['totales'] = [
                'subtotal' => $resultadoCalculo['subtotal'],
                'descuento' => $resultadoCalculo['total_descuento'],
                'total_impuesto' => $resultadoCalculo['total_impuestos'],
                'total' => $resultadoCalculo['total']
            ];
          //  dd($jsonData);
            // Variables para almacenar el resultado del SP
            $facturaId = null;

            // Ejecutar SP y obtener el ID
            DB::statement('SET @factura_id = NULL');
            DB::statement('CALL API_SP_CREAR_FACTURA(?, @factura_id)', [json_encode($jsonData)]);
            $result = DB::select('SELECT @factura_id as id');

            if (empty($result)) {
                throw new FacturacionException('Error al obtener el ID de la factura creada');
            }

            $facturaId = $result[0]->id;

            if (!$facturaId) {
                throw new FacturacionException('Error al crear la factura');
            }

            // Cargar la factura con todas sus relaciones
            /*
            $factura = Factura::with([
                'detalles.impuestos',
                'impuestos',
                'pagos',
                'detallesAdicionales',
                'estados'
            ])->findOrFail($facturaId);

             */
            $factura = Factura::with([
                'detalles.impuestos',
                'impuestos',
                'pagos',
                'detallesAdicionales',
                'estados'
            ])->findOrFail($facturaId);

// Ocultar campos
            $factura->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at',
                'historial_cambios',
                'version_actual'
            ]);

// Ocultar campos en las relaciones
            $factura->detalles->each(function($detalle) {
                $detalle->makeHidden(['created_at', 'updated_at', 'deleted_at']);
                $detalle->impuestos->each(function($impuesto) {
                    $impuesto->makeHidden(['created_at', 'updated_at', 'deleted_at']);
                });
            });

            $factura->impuestos->each(function($impuesto) {
                $impuesto->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });

            $factura->pagos->each(function($pago) {
                $pago->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });

            $factura->detallesAdicionales->each(function($detalle) {
                $detalle->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });

            $factura->estados->each(function($estado) {
                $estado->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });
            DB::disconnect('mysql');

            return $factura;

        } catch (\Exception $e) {
            // Si algo salió mal, revertimos la transacción
            \Log::channel('facturas')->error('Error al procesar factura: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar secuencial para la factura
     */
    protected function generarSecuencial(int $puntoEmision): string
    {
        $siguiente = $puntoEmision + 1;

        if ($siguiente > 999999999) {
            throw new FacturacionException("Se ha superado el límite de secuenciales para este punto de emisión");
        }

        return str_pad($siguiente, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Validar totales de la factura
     */
    protected function validarTotales(array $detalles, array $formasPago): void
    {
        $totalDetalles = collect($detalles)->sum(function ($detalle) {
            $subtotal = $detalle['cantidad'] * $detalle['precio_unitario'];
            $descuento = $detalle['descuento'] ?? 0;
            $totalImpuestos = collect($detalle['impuestos'])->sum('valor');

            return ($subtotal - $descuento) + $totalImpuestos;
        });

        $totalPagos = collect($formasPago)->sum('total');

        if (abs($totalDetalles - $totalPagos) > 0.01) {
            throw new FacturacionException(
                "El total de las formas de pago ({$totalPagos}) no coincide con el total de la factura ({$totalDetalles})"
            );
        }
    }

    /**
     * Generar detalles adicionales por defecto
     */
    protected function generarDetallesAdicionalesDefault(array $datos): array
    {
        return [
            [
                'nombre' => 'Email',
                'valor' => $datos['comprador']['email']
            ],
            [
                'nombre' => 'Dirección',
                'valor' => $datos['comprador']['direccion']
            ],
            [
                'nombre' => 'Identificación',
                'valor' => $datos['comprador']['identificacion']
            ]
        ];
    }

    /**
     * Consultar factura por clave de acceso
     */
    /*
    public function consultarFactura(string $claveAcceso): Factura
    {
        return Factura::where('claveAcceso', $claveAcceso)
            ->with([
                'detalles.impuestos',
                'impuestos',
                'pagos',
                'detallesAdicionales',
                'estados'
            ])
            ->firstOrFail();
    }
    */
    public function consultarFactura(string $claveAcceso): Factura
    {
        $factura = Factura::where('claveAcceso', $claveAcceso)
            ->with([
                'detalles.impuestos',
                'impuestos',
                'pagos',
                'detallesAdicionales',
                'estados'
            ])
            ->firstOrFail();

        // Ocultar campos en la factura principal
        $factura->makeHidden([
            'created_at',
            'updated_at',
            'deleted_at',
            'historial_cambios',
            'version_actual'
        ]);

        // Ocultar campos en las relaciones
        $factura->detalles->each(function($detalle) {
            $detalle->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at'
            ]);

            $detalle->impuestos->each(function($impuesto) {
                $impuesto->makeHidden([
                    'created_at',
                    'updated_at',
                    'deleted_at'
                ]);
            });
        });

        $factura->impuestos->each(function($impuesto) {
            $impuesto->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at'
            ]);
        });

        $factura->pagos->each(function($pago) {
            $pago->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at'
            ]);
        });

        $factura->detallesAdicionales->each(function($detalle) {
            $detalle->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at'
            ]);
        });

        $factura->estados->each(function($estado) {
            $estado->makeHidden([
                'created_at',
                'updated_at',
                'deleted_at'
            ]);
        });

        return $factura;
    }

    /**
     * Anular factura
     */
    public function anularFactura(Factura $factura, string $motivo, string $usuario): void
    {
        if (!in_array($factura->estado, ['CREADA', 'AUTORIZADA'])) {
            throw new FacturacionException("No se puede anular la factura en estado: {$factura->estado}");
        }

        DB::transaction(function () use ($factura, $motivo, $usuario) {
            $factura->update(['estado' => 'ANULADA']);
            FacturaEstado::create([
                'factura_id' => $factura->id,
                'estado_actual' => 'ANULADA',
                'mensaje' => $motivo,
                'usuario' => $usuario,
                'fecha' => Carbon::now()
            ]);

        });
    }
}
