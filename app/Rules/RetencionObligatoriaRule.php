<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class RetencionObligatoriaRule implements Rule
{
    protected $version;
    protected $message = '';

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function passes($attribute, $value): bool
    {
        // Las retenciones son obligatorias desde la versión 2.0.0
        if (version_compare($this->version, '2.0.0', '>=')) {
            if (empty($value) || !is_array($value)) {
                $this->message = 'Las retenciones son obligatorias para esta versión.';
                return false;
            }

            // Validar que al menos exista una retención de IVA o Renta
            $tieneRetencionValida = false;
            foreach ($value as $retencion) {
                if (isset($retencion['codigo']) && in_array($retencion['codigo'], ['1', '2'])) {
                    $tieneRetencionValida = true;
                    break;
                }
            }

            if (!$tieneRetencionValida) {
                $this->message = 'Debe incluir al menos una retención de IVA o Renta.';
                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
