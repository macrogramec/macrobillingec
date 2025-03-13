<?php

namespace App\Services\EndPoints\Empresa;

use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class EmpresaService
{
    public function getAll(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Empresa::query();

        // Aplicar filtros
        if (isset($filters['ruc'])) {
            $query->where('ruc', 'like', "%{$filters['ruc']}%");
        }
        if (isset($filters['razon_social'])) {
            $query->where('razon_social', 'like', "%{$filters['razon_social']}%");
        }
        if (isset($filters['ambiente'])) {
            $query->where('ambiente', $filters['ambiente']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Empresa
    {
        return Empresa::with(['establecimientos.puntosEmision'])->find($id);
    }

    public function create(array $data): Empresa
    {
        if (empty($data['usuario_macrobilling'] ?? null)) {
            // Asignar el ID del usuario logueado al campo 'usuario_macrobilling'
            $data['usuario_macrobilling'] = auth()->id(); // O $_SESSION['id_user'], dependiendo de tu lógica de autenticación
        }
        $data['uuid'] = (string) Str::uuid();


        return DB::transaction(function () use ($data) {
            return Empresa::create($data);
        });
    }

    public function update(Empresa $empresa, array $data): Empresa
    {
        return DB::transaction(function () use ($empresa, $data) {
            $empresa->update($data);
            return $empresa->fresh();
        });
    }

    public function delete(Empresa $empresa): bool
    {
        return DB::transaction(function () use ($empresa) {
            // Verificar si tiene establecimientos
            if ($empresa->establecimientos()->count() > 0) {
                throw new \Exception('No se puede eliminar la empresa porque tiene establecimientos asociados.');
            }
            return $empresa->delete();
        });
    }

    public function updateFirma(Empresa $empresa, string $firmaBase64, string $clave): bool
    {
        return DB::transaction(function () use ($empresa, $firmaBase64, $clave) {
            try {
                // Decodificar firma base64
                $firma = base64_decode($firmaBase64, true);
                if ($firma === false) {
                    \Log::error('Error al decodificar la firma base64');
                    throw new \Exception('Error al procesar el archivo de firma electrónica.');
                }

                // Guardar firma
                $path = "firmasElectronicas/{$empresa->uuid}/{$empresa->ruc}.p12";
                Storage::put($path, $firma);

                // Actualizar empresa
                $empresa->update([
                    'firma_electronica' => $path,
                    'clave_firma' => $clave
                ]);

                return true;
            } catch (\Exception $e) {
                \Log::error('Error en updateFirma', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    private function validarFirma(string $firma, string $clave): bool
    {
        try {
            // Intentar leer el certificado
            $certInfo = [];
            $result = openssl_pkcs12_read($firma, $certInfo, $clave);

            // Registrar el resultado para depuración
            if (!$result) {
                $opensslError = openssl_error_string();
                \Log::error('Error en openssl_pkcs12_read', [
                    'openssl_error' => $opensslError,
                    'firma_length' => strlen($firma)
                ]);
                return false;
            }

            // Verificar que se pudo extraer información del certificado
            if (empty($certInfo) || !isset($certInfo['cert'])) {
                \Log::error('No se pudo extraer información del certificado');
                return false;
            }

            \Log::info('Firma validada correctamente');
            return true;
        } catch (\Exception $e) {
            \Log::error('Excepción al validar firma', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function obtenerFechaVencimiento(string $firma): string
    {
        // Implementar lógica para obtener fecha de vencimiento del certificado
        // Este es un ejemplo básico
        $cert = openssl_x509_read($firma);
        $certData = openssl_x509_parse($cert);
        return date('Y-m-d H:i:s', $certData['validTo_time_t']);
    }
}
