<?php

namespace App\Services;

use App\Models\CodigoRetencion;
use App\Models\Retencion;
use App\Models\RetencionDetalle;
use App\Models\RetencionEstado;
use App\Models\Empresa;
use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use App\Exceptions\RetencionException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\CalculadorRetencionService;

class RetencionService
{

    protected $claveAccesoGenerator;
    protected $calculadorService;
   // protected $validadorService;

    public function __construct(

        ClaveAccesoGenerator $claveAccesoGenerator,
        CalculadorRetencionService $calculadorService,
       // ValidadorRetencionService $validadorService
    ) {

        $this->claveAccesoGenerator = $claveAccesoGenerator;
        $this->calculadorService = $calculadorService;
       // $this->validadorService = $validadorService;
    }

    public function crear(array $datos): Retencion
    {

        return DB::transaction(function () use ($datos) {
            // 1. Validar y obtener entidades relacionadas
            $empresa = Empresa::findOrFail($datos['empresa_id']);
            $establecimiento = Establecimiento::findOrFail($datos['establecimiento_id']);
            $puntoEmision = PuntoEmision::where([
                ['establecimiento_id', '=', $datos['establecimiento_id']],
                ['tipo_comprobante', '=', '07']
            ])->first();
            // 2. Validar ambiente y permisos
            $this->validarAmbiente($empresa, $datos['ambiente']);

            // 3. Generar secuencial
            $secuencial = $this->generarSecuencial($puntoEmision);

            // 4. Generar clave de acceso
            $claveAcceso = $this->claveAccesoGenerator->generate([
                'fecha_emision' => $datos['fecha_emision'],
                'tipo_comprobante' => '07', // Código para retenciones
                'ruc' => $empresa->ruc,
                'tipo_ambiente' => $datos['ambiente'],
                'establecimiento' => $establecimiento->codigo,
                'punto_emision' => $puntoEmision->codigo,
                'secuencial' => $secuencial
            ]);

            // 5. Crear la retención
            $retencion = new Retencion([
                'empresa_id' => $empresa->id,
                'establecimiento_id' => $establecimiento->id,
                'punto_emision_id' => $puntoEmision->id,
                'uuid' => Str::uuid(),
                'estado' => 'CREADA',
                'version' => '1.1.0',
                'ambiente' => $datos['ambiente'],
                'tipo_emision' => $datos['tipo_emision'],
                'razon_social' => $empresa->razon_social,
                'nombre_comercial' => $empresa->nombre_comercial,
                'obligadoContabilidad' => $empresa->obligado_contabilidad,
                'dirEstablecimiento' => $establecimiento->direccion,
                'ruc' => $empresa->ruc,
                'clave_acceso' => $claveAcceso,
                'cod_doc' => '07',
                'estab' => $establecimiento->codigo,
                'pto_emi' => $puntoEmision->codigo,
                'secuencial' => $secuencial,
                'fechaEmision' => $datos['fecha_emision'],
                'periodo_fiscal' => $datos['periodo_fiscal'],
                'dir_matriz' => $empresa->direccion_matriz,
                'tipo_identificacion_sujeto' => $datos['sujeto']['tipo_identificacion'],
                'razon_social_sujeto' => $datos['sujeto']['razon_social'],
                'identificacion_sujeto' => $datos['sujeto']['identificacion'],
                'tipo_sujeto' => $datos['sujeto']['tipo_sujeto'],
                'regimen_sujeto' => $datos['sujeto']['regimen'],
                'total_retenido' => 0
            ]);

            $retencion->save();

            // 6. Procesar detalles
            $this->procesarDetalles($retencion, $datos['detalles']);

            // 7. Calcular totales
            $totales = $this->calculadorService->calcularTotales($retencion);
            $retencion->update(['total_retenido' => $totales['total_retenido']]);

            // 8. Crear estado inicial
            $this->crearEstadoInicial($retencion);

            // 9. Procesar información adicional si existe
            $this->procesarInfoAdicional($retencion, $datos['info_adicional']);


            // 10. Actualizar secuencial en punto de emisión
            $puntoEmision->update([
                'secuencial_actual' => $secuencial
            ]);

            return $retencion->fresh(['detalles', 'estados', 'detallesAdicionales']);
        });
    }

    protected function validarAmbiente(Empresa $empresa, string $ambiente): void
    {
        // Implementar validaciones de ambiente
        if ($ambiente === '2' && !$empresa->puede_emitir_produccion) {
            throw new RetencionException('La empresa no está autorizada para emitir en ambiente de producción');
        }
    }

    protected function generarSecuencial(PuntoEmision $puntoEmision): string
    {
        $siguiente = $puntoEmision->secuencial_actual + 1;

        if ($siguiente > 999999999) {
            throw new RetencionException('Se ha superado el límite de secuenciales para este punto de emisión');
        }

        return str_pad($siguiente, 9, '0', STR_PAD_LEFT);
    }

    protected function procesarDetalles(Retencion $retencion, array $detalles): void
    {


        foreach ($detalles as $index => $detalle) {
            $codigoRetencion = CodigoRetencion::activos()
                ->where('tipo_impuesto', $detalle['tipo_impuesto'])
                ->where('codigo', $detalle['codigo'])
                ->firstOrFail();

            RetencionDetalle::create([
                'retencion_id' => $retencion->id,
                'linea' => $index + 1,
                'codigo' => $detalle['codigo'],
                'tipo_impuesto' => $detalle['tipo_impuesto'],
                'codigo_retencion_id' => $codigoRetencion->id,
                'base_imponible' => $detalle['base_imponible'],
                'porcentaje_retener' => $codigoRetencion->porcentaje,
                'valor_retenido' => ($detalle['base_imponible'] * $codigoRetencion->porcentaje) / 100,
                'cod_doc_sustento' => $detalle['doc_sustento']['codigo'],
                'num_doc_sustento' => $detalle['doc_sustento']['numero'],
                'fecha_emision_doc_sustento' => $detalle['doc_sustento']['fecha_emision']
            ]);
        }
    }

    protected function crearEstadoInicial(Retencion $retencion): void
    {
        RetencionEstado::create([
            'retencion_id' => $retencion->id,
            'estado_actual' => 'CREADA',
            'estado_sri' => null,
            'ip_origen' => request()->ip(),
            'usuario_proceso' => auth()->user()->name
        ]);
    }

    protected function procesarInfoAdicional(Retencion $retencion, array $infoAdicional): void
    {
        foreach ($infoAdicional as $info) {
            $retencion->detallesAdicionales()->create([
                'retencion_id' => $retencion->id,
                'nombre' => $info['nombre'],
                'valor' => $info['valor'],
                'orden' => $info['orden'] ?? 0
            ]);
        }
    }

    public function anular(Retencion $retencion, string $motivo): void
    {
        if (!in_array($retencion->estado, ['CREADA', 'AUTORIZADA'])) {
            throw new RetencionException("No se puede anular la retención en estado: {$retencion->estado}");
        }

        DB::transaction(function () use ($retencion, $motivo) {
            $retencion->update(['estado' => 'ANULADA']);

            $retencion->estados()->create([
                'estado_actual' => 'ANULADA',
                'mensaje' => $motivo,
                'usuario' => auth()->user()->name,
                'fecha' => now()
            ]);
        });
    }

    public function consultarPorClaveAcceso(string $claveAcceso): Retencion
    {
        return Retencion::where('clave_acceso', $claveAcceso)
            ->with(['detalles', 'estados'])
            ->firstOrFail();
    }

    public function generarPDF(Retencion $retencion): string
    {
        // Implementar generación de PDF
        // Retorna el contenido del PDF en base64
    }

    public function generarXML(Retencion $retencion): string
    {
        //return $this->xmlGenerator->generarXMLRetencion($retencion);
    }

    public function actualizarEstadoSRI(Retencion $retencion, array $respuestaSRI): void
    {
        DB::transaction(function () use ($retencion, $respuestaSRI) {
            $retencion->update([
                'estado' => $respuestaSRI['estado'],
                'numero_autorizacion' => $respuestaSRI['numeroAutorizacion'] ?? null,
                'fecha_autorizacion' => isset($respuestaSRI['fechaAutorizacion'])
                    ? Carbon::parse($respuestaSRI['fechaAutorizacion'])
                    : null
            ]);

            $retencion->estados()->create([
                'estado_actual' => $respuestaSRI['estado'],
                'estado_sri' => $respuestaSRI['estado'],
                'respuesta_sri' => $respuestaSRI,
                'fecha' => now(),
                'usuario' => 'SISTEMA'
            ]);
        });
    }
}
