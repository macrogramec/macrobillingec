<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            // 0. Verificar si existe el tipo de impuesto IV
            $tipoIVA = DB::table('tipos_impuestos')
                ->where('codigo', 'IV')
                ->first();

            // Si no existe, crearlo
            if (!$tipoIVA) {
                DB::table('tipos_impuestos')->insert([
                    'codigo' => 'IV',
                    'descripcion' => 'Impuesto al Valor Agregado',
                    'codigo_sri' => '2',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 1. Verificar si ya existe la tarifa IVA 15%
            $tarifaExistente = DB::table('tarifas_impuestos')
                ->where('tipo_impuesto_codigo', 'IV')
                ->where('codigo', 'IV15')
                ->first();

            // Solo proceder si no existe
            if (!$tarifaExistente) {
                // 2. Desactivar tarifa anterior IVA
                DB::table('tarifas_impuestos')
                    ->where('codigo_sri', '2')
                    ->where('tipo_impuesto_codigo', 'IV')
                    ->update([
                        'fecha_fin' => '2024-04-21',
                        'activo' => false,
                        'updated_at' => now()
                    ]);

                // 3. Crear nueva tarifa IVA 15%
                DB::table('tarifas_impuestos')->insertGetId([
                    'tipo_impuesto_codigo' => 'IV',
                    'codigo' => 'IV15',
                    'codigo_sri' => '2',
                    'descripcion' => 'IVA 15%',
                    'porcentaje' => 15.00,
                    'tipo_calculo' => 'PORCENTAJE',
                    'fecha_inicio' => '2024-04-22',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // 4. Obtener el ID de la nueva tarifa
                $nuevaTarifaId = DB::table('tarifas_impuestos')
                    ->where('codigo', 'IV15')
                    ->value('id');

                // 5. Registrar en historial si se obtuvo el ID
                if ($nuevaTarifaId) {
                    DB::table('historial_tarifas')->insert([
                        'tarifa_impuesto_id' => $nuevaTarifaId,
                        'porcentaje_anterior' => 12.00,
                        'porcentaje_nuevo' => 15.00,
                        'motivo' => 'Actualización IVA 2024 - Decreto Ejecutivo',
                        'documento_respaldo' => 'decreto_ejecutivo_2024.pdf',
                        'usuario' => 'sistema',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Error al actualizar tarifas IVA: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            // 1. Eliminar registro del historial
            DB::table('historial_tarifas')
                ->where('motivo', 'Actualización IVA 2024 - Decreto Ejecutivo')
                ->delete();

            // 2. Eliminar tarifa IVA 15%
            DB::table('tarifas_impuestos')
                ->where('tipo_impuesto_codigo', 'IV')
                ->where('codigo', 'IV15')
                ->delete();

            // 3. Reactivar tarifa anterior
            DB::table('tarifas_impuestos')
                ->where('codigo_sri', '2')
                ->where('tipo_impuesto_codigo', 'IV')
                ->update([
                    'fecha_fin' => null,
                    'activo' => true,
                    'updated_at' => now()
                ]);
        } catch (\Exception $e) {
            throw new \Exception('Error al revertir actualización de IVA: ' . $e->getMessage());
        }
    }
};
