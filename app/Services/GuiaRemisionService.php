<?php

namespace App\Services;

use App\Models\{GuiaRemision, GuiaRemisionDestinatario, GuiaRemisionDetalle, GuiaRemisionEstado, PuntoEmision, Empresa, Establecimiento};
use App\Exceptions\GuiaRemisionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GuiaRemisionService
{
    protected ClaveAccesoGenerator $claveAccesoGenerator;
    protected $validadorService;
    protected string $version;
    public function __construct(
        ValidadorGuiaRemisionService $validadorService,
         ClaveAccesoGenerator $claveAccesoGenerator
    ) {
        $this->validadorService = $validadorService;
        $this->claveAccesoGenerator = $claveAccesoGenerator;
    }

    /**
     * Procesa y crea una nueva guía de remisión
     */
    public function procesarGuiaRemision(array $datos): GuiaRemision
    {
        return DB::transaction(function () use ($datos) {
            // Validar datos básicos
            $this->version = $datos['version'] ?? '1.1.0';
            $this->validadorService->validarDatosBasicos($datos);

            // Obtener entidades
            $empresa = Empresa::findOrFail($datos['empresa_id']);
            $establecimiento = Establecimiento::findOrFail($datos['establecimiento_id']);
            $puntoEmision = PuntoEmision::findOrFail($datos['punto_emision_id']);

            // Generar secuencial
            $secuencial = $this->obtenerSiguienteSecuencial($puntoEmision->id);
            $fecha_emision = Carbon::parse($datos['fecha_ini_transporte'])->format('d/m/Y');
            // Generar clave de acceso
            $claveAcceso = $this->claveAccesoGenerator->generate([
                'fecha_emision' => $fecha_emision,
                'tipo_comprobante' => '06',
                'ruc' => $datos['empresa']['ruc'],
                'tipo_ambiente' => $datos['ambiente'],
                'establecimiento' => $datos['establecimiento']['codigo'],
                'punto_emision' => $datos['punto_emision']['codigo'],
                'secuencial' => $secuencial
            ]);

            //$claveAcceso =0;

            // Crear guía de remisión
            $guiaRemision = GuiaRemision::create([
                'uuid' => Str::uuid(),
                'estado' => 'CREADA',
                'version' => $this->version,
                'empresa_id' => $empresa->id,
                'establecimiento_id' => $establecimiento->id,
                'punto_emision_id' => $puntoEmision->id,
                'ambiente' => $datos['ambiente'],
                'tipo_emision' => $datos['tipo_emision'],
                'razonSocial' => $empresa->razon_social,
                'nombreComercial' => $empresa->nombre_comercial,
                'ruc' => $empresa->ruc,
                'claveAcceso' => $claveAcceso,
                'codDoc' => '06',
                'estab' => $establecimiento->codigo,
                'ptoEmi' => $puntoEmision->codigo,
                'secuencial' => $secuencial,
                'dirMatriz' => $empresa->direccion_matriz,
                'dirEstablecimiento' => $establecimiento->direccion,
                'dirPartida' => $datos['dir_partida'],
                'razonSocialTransportista' => $datos['transportista']['razon_social'],
                'tipoIdentificacionTransportista' => $datos['transportista']['tipo_identificacion'],
                'rucTransportista' => $datos['transportista']['identificacion'],
                'rise' => $datos['transportista']['rise'] ?? null,
                'obligadoContabilidad' => $empresa->obligado_contabilidad,
                'contribuyenteEspecial' => $empresa->contribuyente_especial,
                'fechaIniTransporte' => $datos['fecha_ini_transporte'],
                'fechaFinTransporte' => $datos['fecha_fin_transporte'],
                'placa' => $datos['transportista']['placa']
            ]);

            // Procesar destinatarios y sus detalles
            $this->procesarDestinatarios($guiaRemision, $datos['destinatarios']);

            // Procesar información adicional si existe
            if (isset($datos['info_adicional'])) {
                $this->procesarInfoAdicional($guiaRemision, $datos['info_adicional']);
            }

            // Crear estado inicial
            $this->crearEstadoInicial($guiaRemision);

            // Actualizar secuencial
            $puntoEmision->update(['secuencial_actual' => $secuencial]);

            return $guiaRemision->fresh(['destinatarios.detalles', 'estados']);
        });
    }

    /**
     * Procesa los destinatarios de la guía de remisión
     */
    protected function procesarDestinatarios(GuiaRemision $guiaRemision, array $destinatarios): void
    {
        foreach ($destinatarios as $destinatario) {
            $nuevoDestinatario = GuiaRemisionDestinatario::create([
                'guia_remision_id' => $guiaRemision->id,
                'identificacionDestinatario' => $destinatario['identificacion'],
                'razonSocialDestinatario' => $destinatario['razon_social'],
                'dirDestinatario' => $destinatario['direccion'],
                'email' => $destinatario['email'],
                'motivoTraslado' => $destinatario['motivo_traslado'],
                'docAduaneroUnico' => $destinatario['doc_aduanero'] ?? null,
                'codEstabDestino' => $destinatario['cod_establecimiento_destino'] ?? null,
                'ruta' => $destinatario['ruta'] ?? null,
                'codDocSustento' => $destinatario['doc_sustento']['tipo'] ?? null,
                'numDocSustento' => $destinatario['doc_sustento']['numero'] ?? null,
                'numAutDocSustento' => $destinatario['doc_sustento']['autorizacion'] ?? null,
                'fechaEmisionDocSustento' => isset($destinatario['doc_sustento']['fecha_emision']) ?
                    Carbon::parse($destinatario['doc_sustento']['fecha_emision']) : null
            ]);

            // Procesar detalles de cada destinatario
            $this->procesarDetalles($nuevoDestinatario, $destinatario['detalles']);
        }
    }

    /**
     * Procesa los detalles de cada destinatario
     */
    protected function procesarDetalles(GuiaRemisionDestinatario $destinatario, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            GuiaRemisionDetalle::create([
                'guia_remision_destinatario_id' => $destinatario->id,
                'codigoInterno' => $detalle['codigo_interno'] ?? null,
                'codigoAdicional' => $detalle['codigo_adicional'] ?? null,
                'descripcion' => $detalle['descripcion'],
                'cantidad' => $detalle['cantidad'],
                'detallesAdicionales' => isset($detalle['detalles_adicionales']) ?
                    json_encode($detalle['detalles_adicionales']) : null
            ]);
        }
    }

    /**
     * Procesa información adicional de la guía de remisión
     */
    protected function procesarInfoAdicional(GuiaRemision $guiaRemision, array $infoAdicional): void
    {
        $infoAdic = [];
        foreach ($infoAdicional as $info) {
            $infoAdic[] = [
                'nombre' => $info['nombre'],
                'valor' => $info['valor']
            ];
        }
        $guiaRemision->update(['infoAdicional' => $infoAdic]);
    }

    /**
     * Crea el estado inicial de la guía de remisión
     */
    protected function crearEstadoInicial(GuiaRemision $guiaRemision): void
    {
        GuiaRemisionEstado::create([
            'guia_remision_id' => $guiaRemision->id,
            'estado_actual' => 'CREADA',
            'ip_origen' => request()->ip(),
            'usuario_proceso' => auth()->user()->name ?? 'Sistema'
        ]);
    }

    /**
     * Obtiene el siguiente secuencial disponible
     */
    public function obtenerSiguienteSecuencial(int $puntoEmisionId): string
    {
        $puntoEmision = PuntoEmision::findOrFail($puntoEmisionId);
        $siguiente = $puntoEmision->secuencial_actual + 1;

        if ($siguiente > 999999999) {
            throw new GuiaRemisionException("Se ha superado el límite de secuenciales para este punto de emisión");
        }

        return str_pad($siguiente, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Anula una guía de remisión
     */
    public function anularGuiaRemision(GuiaRemision $guiaRemision, string $motivo): void
    {
        if (!in_array($guiaRemision->estado, ['CREADA', 'AUTORIZADA'])) {
            throw new GuiaRemisionException("No se puede anular la guía de remisión en estado: {$guiaRemision->estado}");
        }

        DB::transaction(function () use ($guiaRemision, $motivo) {
            $guiaRemision->update(['estado' => 'ANULADA']);

            $guiaRemision->estados()->create([
                'estado_actual' => 'ANULADA',
                'estado_sri' => 'ANULADA',
                'anulado' => true,
                'fecha_anulacion' => Carbon::now(),
                'motivo_anulacion' => $motivo,
                'ip_origen' => request()->ip(),
                'usuario_proceso' => auth()->user()->name ?? 'Sistema'
            ]);
        });
    }

    /**
     * Consulta una guía de remisión por su clave de acceso
     */
    public function consultarPorClaveAcceso(string $claveAcceso): GuiaRemision
    {
        return GuiaRemision::where('claveAcceso', $claveAcceso)
            ->with(['destinatarios.detalles', 'estados'])
            ->firstOrFail();
    }
}
