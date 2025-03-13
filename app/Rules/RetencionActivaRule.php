<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CodigoRetencion;
use Carbon\Carbon;

class RetencionActivaRule implements Rule
{
    private $message;

    public function passes($attribute, $value): bool
    {
        try {
            $codigoRetencion = CodigoRetencion::find($value);

            if (!$codigoRetencion) {
                $this->message = 'El código de retención no existe.';
                return false;
            }

            // Verificar si está activo
            if (!$codigoRetencion->activo) {
                $this->message = 'El código de retención no está activo.';
                return false;
            }

            // Verificar fechas de vigencia
            $today = Carbon::now();

            if ($today->lt($codigoRetencion->fecha_inicio)) {
                $this->message = 'El código de retención aún no está vigente.';
                return false;
            }

            if ($codigoRetencion->fecha_fin && $today->gt($codigoRetencion->fecha_fin)) {
                $this->message = 'El código de retención ha expirado.';
                return false;
            }

            // Verificar si aplica para el tipo de persona/régimen
            if ($codigoRetencion->tipo_persona) {
                $tipoPersona = request()->input('sujeto.tipo_sujeto');
                if ($codigoRetencion->tipo_persona !== $tipoPersona) {
                    $this->message = 'El código de retención no aplica para este tipo de persona.';
                    return false;
                }
            }

            if ($codigoRetencion->tipo_regimen) {
                $regimen = request()->input('sujeto.regimen');
                if ($codigoRetencion->tipo_regimen !== $regimen) {
                    $this->message = 'El código de retención no aplica para este régimen.';
                    return false;
                }
            }

            // Verificar validaciones específicas si existen
            if ($codigoRetencion->validaciones) {
                $validaciones = json_decode($codigoRetencion->validaciones, true);
                foreach ($validaciones as $validacion) {
                    if (!$this->validarRegla($validacion)) {
                        $this->message = $validacion['mensaje'] ?? 'No cumple con las validaciones requeridas.';
                        return false;
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Error en RetencionActivaRule: ' . $e->getMessage());
            $this->message = 'Error al validar el código de retención.';
            return false;
        }
    }

    private function validarRegla(array $validacion): bool
    {
        $valor = request()->input($validacion['campo']);

        switch ($validacion['operador']) {
            case '>':
                return $valor > $validacion['valor'];
            case '>=':
                return $valor >= $validacion['valor'];
            case '<':
                return $valor < $validacion['valor'];
            case '<=':
                return $valor <= $validacion['valor'];
            case '=':
                return $valor == $validacion['valor'];
            case '!=':
                return $valor != $validacion['valor'];
            case 'in':
                return in_array($valor, $validacion['valores']);
            case 'not_in':
                return !in_array($valor, $validacion['valores']);
            default:
                return false;
        }
    }

    public function message(): string
    {
        return $this->message ?? 'El código de retención no es válido.';
    }
}
