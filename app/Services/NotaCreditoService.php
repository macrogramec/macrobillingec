<?php

namespace App\Services;

use App\Models\{NotaCredito, NotaCreditoDetalle, NotaCreditoEstado, NotaCreditoImpuesto, Factura, PuntoEmision};
use App\Exceptions\NotaCreditoException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotaCreditoService
{
    protected $validadorService;
    protected $calculadorService;
    protected $claveAccesoGenerator;

    public function __construct(
        ValidadorNotaCreditoService $validadorService,
        CalculadorNotaCreditoService $calculadorService,
        ClaveAccesoGenerator $claveAccesoGenerator
    ) {
        $this->validadorService = $validadorService;
        $this->calculadorService = $calculadorService;
        $this->claveAccesoGenerator = $claveAccesoGenerator;
    }

    /**
     * Procesa una nota de crédito externa (no emitida en el sistema)
     */
    public function procesarNotaCreditoExterna(array $datos): NotaCredito
    {
        Log::channel('notas_credito')->info('datos', $datos);
        return DB::transaction(function () use ($datos) {
            // Validar datos básicos
            $this->validadorService->validarDatosBasicos($datos);

            // Generar clave de acceso
            $claveAcceso = $this->claveAccesoGenerator->generate([
                'fecha_emision' => Carbon::parse($datos['fecha_emision']),
                'tipo_comprobante' => '04', // 04: Nota de Crédito
                'ruc' => $datos['empresa']['ruc'],
                'tipo_ambiente' => $datos['ambiente'],
                'establecimiento' => $datos['establecimiento']['codigo'],
                'punto_emision' => $datos['punto_emision']['codigo'],
                'secuencial' => $datos['secuencial']
            ]);

            $notaCredito = NotaCredito::create([
                'empresa_id' => $datos['empresa_id'],
                'establecimiento_id' => $datos['establecimiento_id'],
                'punto_emision_id' => $datos['punto_emision_id'],
                'uuid' => (string) Str::uuid(),
                'estado' => 'CREADA',
                'version' => '1.1.0',
                'ambiente' => $datos['ambiente'],
                'tipoEmision' => $datos['tipo_emision'],           // Cambio aquí
                'razonSocial' => $datos['empresa']['razon_social'], // Cambio aquí
                'dirMatriz' => $datos['empresa']['direccion_matriz'],
                'nombreComercial' => $datos['empresa']['nombre_comercial'], // Cambio aquí
                'ruc' => $datos['empresa']['ruc'],
                'claveAcceso' => $claveAcceso, // Este campo es requerido
                'codDoc' => '04',              // Cambio aquí
                'estab' => $datos['establecimiento']['codigo'],
                'ptoEmi' => $datos['punto_emision']['codigo'],
                'dirEstablecimiento' => $datos['establecimiento']['direccion'],
                'contribuyenteEspecial' => $datos['empresa']['contribuyente_especial'],
                'obligadoContabilidad'=> $datos['empresa']['obligado_contabilidad'],
                'secuencial' => $datos['secuencial'],
                'fechaEmision' => $datos['fecha_emision'],         // Cambio aquí
                // Datos del comprador
                'tipoIdentificacionComprador' => $datos['comprador']['tipo_identificacion'], // Cambio aquí
                'razonSocialComprador' => $datos['comprador']['razon_social'],  // Cambio aquí
                'identificacionComprador' => $datos['comprador']['identificacion'],
                // Documento modificado
                'codDocModificado' => $datos['doc_modificado']['tipo_doc'],    // Cambio aquí
                'numDocModificado' => $datos['doc_modificado']['numero'],      // Cambio aquí
                'fechaEmisionDocSustento' => $datos['doc_modificado']['fecha_emision'], // Cambio aquí
                'motivo' => $datos['doc_modificado']['motivo'],
                'totalSinImpuestos' => 0,
                'valorModificacion' => 0,
                'totalDescuento' => 0,
                'totalImpuestos' => 0,
                'valorTotal' => 0
            ]);

            // Procesar detalles
            $this->procesarDetalles($notaCredito, $datos['detalles']);
       // dd($datos['detalles']);
            // Calcular totales
            $totales = $this->calculadorService->calcularTotales($datos['detalles']);
            $notaCredito->update($totales);

            // Generar estado inicial
            $notaCredito->estados()->create([
                'estado_actual' => 'CREADA',
                'usuario_proceso' => auth()->user()->name ?? 'Sistema',
                'fecha_proceso' => now()
            ]);

            return $notaCredito->fresh(['detalles', 'estados', 'impuestos']);
        });
    }

    /**
     * Procesa una nota de crédito interna (emitida en el sistema)
     */
    public function procesarNotaCreditoInterna(array $datos): NotaCredito
    {
        return DB::transaction(function () use ($datos) {
            // Obtener la factura original
            $factura = Factura::findOrFail($datos['factura_id']);

            // Validar si la factura puede tener nota de crédito
            $this->validadorService->validarFacturaParaNC($factura, $datos['tipo_aplicacion']);

            // Validar montos y cantidades según tipo de aplicación
            if ($datos['tipo_aplicacion'] === 'PARCIAL') {
                $this->validadorService->validarAplicacionParcial($factura, $datos['detalles']);
            } else {
                $this->validadorService->validarAplicacionTotal($factura, $datos['detalles']);
            }

            // Crear nota de crédito basada en la factura
            $notaCredito = NotaCredito::create([
                'empresa_id' => $factura->empresa_id,
                'establecimiento_id' => $factura->establecimiento_id,
                'punto_emision_id' => $factura->punto_emision_id,
                'factura_id' => $factura->id,
                'uuid' => (string) Str::uuid(),
                'estado' => 'CREADA',
                'version' => '2.1.0',
                'ambiente' => $factura->ambiente,
                'tipo_emision' => '1',
                'razon_social' => $factura->razon_social,
                'nombre_comercial' => $factura->nombre_comercial,
                'ruc' => $factura->ruc,
                // Generar nueva clave de acceso
                'clave_acceso' => $this->claveAccesoGenerator->generate([
                    'fecha_emision' => now(),
                    'tipo_comprobante' => '04',
                    'ruc' => $factura->ruc,
                    'ambiente' => $factura->ambiente,
                    'establecimiento' => $factura->estab,
                    'punto_emision' => $factura->ptoEmi,
                    'secuencial' => $this->obtenerSiguienteSecuencial($factura->punto_emision_id)
                ]),
                'cod_doc' => '04',
                'estab' => $factura->estab,
                'ptoEmi' => $factura->ptoEmi,
                'fecha_emision' => now(),
                'tipo_identificacion_comprador' => $factura->tipo_identificacion_comprador,
                'identificacion_comprador' => $factura->identificacion_comprador,
                'razon_social_comprador' => $factura->razon_social_comprador,
                'cod_doc_modificado' => '01',
                'num_doc_modificado' => "{$factura->estab}-{$factura->ptoEmi}-{$factura->secuencial}",
                'fecha_emision_doc_sustento' => $factura->fecha_emision,
                'motivo' => $datos['motivo_general']
            ]);

            // Procesar detalles según tipo de aplicación
            if ($datos['tipo_aplicacion'] === 'PARCIAL') {
                $this->procesarDetallesParciales($notaCredito, $datos['detalles']);
            } else {
                $this->procesarDetallesTotales($notaCredito, $factura);
            }

            // Calcular totales
            $totales = $this->calculadorService->calcularTotales($notaCredito);
            $notaCredito->update($totales);

            // Registrar estado inicial
            $notaCredito->estados()->create([
                'estado_actual' => 'CREADA',
                'usuario_proceso' => auth()->user()->name ?? 'Sistema',
                'fecha_proceso' => now()
            ]);

            return $notaCredito->fresh(['detalles', 'estados', 'impuestos']);
        });
    }

    /**
     * Procesa los detalles de una nota de crédito
     */
    protected function procesarDetalles(NotaCredito $notaCredito, array $detalles): void
    {

        foreach ($detalles as $index => $detalle) {
            $impuestos = $detalle['impuestos'];
            $primerImpuesto = $impuestos[0] ?? null;

            // Crear detalle de nota de crédito
            $detalleNC = NotaCreditoDetalle::create([
                'nota_credito_id' => $notaCredito->id,
                'linea' => $index + 1,
                'codigoPrincipal' => $detalle['codigo_principal'],
                'codigoAuxiliar' => $detalle['codigo_auxiliar'] ?? null,
                'descripcion' => $detalle['descripcion'],
                'cantidad' => $detalle['cantidad'],
                'precioUnitario' => $detalle['precio_unitario'],
                'descuento' => $detalle['descuento'] ?? 0,
                'precioTotalSinImpuesto' => $detalle['cantidad'] * $detalle['precio_unitario'],
                // Campos de impuesto del primer impuesto
                'impuesto_codigo' => $primerImpuesto['codigo'],
                'impuesto_codigoPorcentaje' => $primerImpuesto['codigo_porcentaje'],
                'impuesto_tarifa' => $primerImpuesto['tarifa'],
                'impuesto_baseImponible' => $primerImpuesto['base_imponible'],
                'impuesto_valor' => $primerImpuesto['valor'],
                'version' => '1.1.0'
            ]);
          //  dd($detalle['impuestos']);
            // Procesar impuestos del detalle


            foreach ($impuestos as $impuesto) {

                NotaCreditoImpuesto::create([
                    'nota_credito_id' => $notaCredito->id,
                    'nota_credito_detalle_id' => $detalleNC->id,
                    'tipo_impuesto_codigo' => $impuesto['codigo'],
                    'tarifa_codigo' => $impuesto['codigo_porcentaje'],
                    'base_imponible' => $impuesto['base_imponible'],
                    'porcentaje' => $impuesto['tarifa'],
                    'valor' => $impuesto['valor'],
                    'version' => '1.1.0',
                    'activo' => true
                ]);

            }
        }
    }

    /**
     * Procesa los detalles para una nota de crédito parcial
     */
    protected function procesarDetallesParciales(NotaCredito $notaCredito, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            // Obtener el detalle original de la factura
            $facturaDetalle = FacturaDetalle::findOrFail($detalle['factura_detalle_id']);

            // Validar cantidad a devolver
            if ($detalle['cantidad_devuelta'] > $facturaDetalle->cantidad_disponible) {
                throw new NotaCreditoException(
                    "La cantidad a devolver excede la cantidad disponible para el ítem {$facturaDetalle->descripcion}"
                );
            }

            // Crear el detalle de la nota de crédito
            $detalleNC = NotaCreditoDetalle::create([
                'nota_credito_id' => $notaCredito->id,
                'factura_detalle_id' => $facturaDetalle->id,
                'codigo_principal' => $facturaDetalle->codigo_principal,
                'codigo_auxiliar' => $facturaDetalle->codigo_auxiliar,
                'descripcion' => $facturaDetalle->descripcion,
                'cantidad' => $detalle['cantidad_devuelta'],
                'precio_unitario' => $facturaDetalle->precio_unitario,
                'descuento' => ($facturaDetalle->descuento / $facturaDetalle->cantidad) * $detalle['cantidad_devuelta'],
                'precio_total_sin_impuesto' => $detalle['cantidad_devuelta'] * $facturaDetalle->precio_unitario
            ]);

            // Actualizar cantidad disponible en el detalle de la factura
            $facturaDetalle->update([
                'cantidad_disponible' => $facturaDetalle->cantidad_disponible - $detalle['cantidad_devuelta']
            ]);

            // Procesar impuestos proporcionalmente
            foreach ($facturaDetalle->impuestos as $impuestoOriginal) {
                $proporcion = $detalle['cantidad_devuelta'] / $facturaDetalle->cantidad;

                NotaCreditoImpuesto::create([
                    'nota_credito_id' => $notaCredito->id,
                    'nota_credito_detalle_id' => $detalleNC->id,
                    'factura_impuesto_id' => $impuestoOriginal->id,
                    'codigo' => $impuestoOriginal->codigo,
                    'codigo_porcentaje' => $impuestoOriginal->codigo_porcentaje,
                    'base_imponible' => $impuestoOriginal->base_imponible * $proporcion,
                    'valor' => $impuestoOriginal->valor * $proporcion
                ]);
            }
        }
    }

    /**
     * Procesa los detalles para una nota de crédito total
     */
    protected function procesarDetallesTotales(NotaCredito $notaCredito, Factura $factura): void
    {
        foreach ($factura->detalles as $facturaDetalle) {
            // Crear el detalle de la nota de crédito
            $detalleNC = NotaCreditoDetalle::create([
                'nota_credito_id' => $notaCredito->id,
                'factura_detalle_id' => $facturaDetalle->id,
                'codigo_principal' => $facturaDetalle->codigo_principal,
                'codigo_auxiliar' => $facturaDetalle->codigo_auxiliar,
                'descripcion' => $facturaDetalle->descripcion,
                'cantidad' => $facturaDetalle->cantidad,
                'precio_unitario' => $facturaDetalle->precio_unitario,
                'descuento' => $facturaDetalle->descuento,
                'precio_total_sin_impuesto' => $facturaDetalle->precio_total_sin_impuesto
            ]);

            // Actualizar cantidad disponible a 0 en el detalle de la factura
            $facturaDetalle->update(['cantidad_disponible' => 0]);

            // Copiar los impuestos exactamente igual
            foreach ($facturaDetalle->impuestos as $impuestoOriginal) {
                NotaCreditoImpuesto::create([
                    'nota_credito_id' => $notaCredito->id,
                    'nota_credito_detalle_id' => $detalleNC->id,
                    'factura_impuesto_id' => $impuestoOriginal->id,
                    'codigo' => $impuestoOriginal->codigo,
                    'codigo_porcentaje' => $impuestoOriginal->codigo_porcentaje,
                    'base_imponible' => $impuestoOriginal->base_imponible,
                    'valor' => $impuestoOriginal->valor
                ]);
            }
        }
    }

    /**
     * Obtiene el siguiente secuencial disponible
     */
    public function obtenerSiguienteSecuencial(int $puntoEmisionId): string
    {
        $puntoEmision = PuntoEmision::findOrFail($puntoEmisionId);

        $siguiente = $puntoEmision->secuencial_actual + 1;

        if ($siguiente > 999999999) {
            throw new NotaCreditoException("Se ha superado el límite de secuenciales para este punto de emisión");
        }

        $puntoEmision->update(['secuencial_actual' => $siguiente]);
        return str_pad($siguiente, 9, '0', STR_PAD_LEFT);
    }
    /**
     * Anular factura
     */
    public function anularNotaCredito(NotaCredito $notaCredito, string $motivo, string $usuario): void
    {


        DB::transaction(function () use ($notaCredito, $motivo, $usuario) {
            $notaCredito->update(['estado' => 'ANULADA']);

           $nota_credito= NotaCreditoEstado::create([
                'nota_credito_id' => $notaCredito->id,
                'estado_actual' => 'ANULADA',
                'motivo_anulacion' => $motivo,
                'anulado' => 1,
                'usuario_proceso' => $usuario,
                'fecha_anulacion' => Carbon::now()
            ]);

        });
    }
}
