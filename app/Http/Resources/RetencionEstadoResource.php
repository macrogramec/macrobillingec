<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RetencionEstadoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'estado_actual' => $this->estado_actual,
            'estado_sri' => $this->estado_sri,

            'firma' => [
                'fecha' => $this->fecha_firma?->format('Y-m-d H:i:s'),
                'exitoso' => $this->firmado_exitoso,
                'error' => $this->error_firma
            ],

            'envio_sri' => [
                'fecha' => $this->fecha_envio_sri?->format('Y-m-d H:i:s'),
                'codigo' => $this->codigo_envio_sri,
                'mensaje' => $this->mensaje_envio_sri,
                'exitoso' => $this->envio_exitoso
            ],

            'autorizacion' => [
                'fecha' => $this->fecha_autorizacion?->format('Y-m-d H:i:s'),
                'numero' => $this->numero_autorizacion,
                'ambiente' => $this->ambiente_autorizacion,
                'respuesta' => $this->respuesta_autorizacion_sri
            ],

            'errores' => $this->errores,
            'numero_intentos' => $this->numero_intentos,
            'usuario_proceso' => $this->usuario_proceso,
            'ip_origen' => $this->ip_origen,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s')
        ];
    }
}
