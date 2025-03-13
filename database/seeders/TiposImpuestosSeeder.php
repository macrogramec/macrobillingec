<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposImpuestosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposImpuestos = [
            ['codigo' => 'IR', 'descripcion' => 'Impuesto a la Renta', 'codigo_sri' => '01', 'activo' => true],
            ['codigo' => 'IV', 'descripcion' => 'Impuesto al Valor Agregado', 'codigo_sri' => '02', 'activo' => true],
            ['codigo' => 'IC', 'descripcion' => 'Impuesto a los Consumos Especiales', 'codigo_sri' => '03', 'activo' => true],
            ['codigo' => 'IS', 'descripcion' => 'Impuesto a la Salida de Divisas', 'codigo_sri' => '04', 'activo' => true],
            ['codigo' => 'IA', 'descripcion' => 'Impuesto Ambiental a la Contaminación Vehicular', 'codigo_sri' => '05', 'activo' => true],
            ['codigo' => 'IB', 'descripcion' => 'Impuesto Redimible a las Botellas Plásticas no Retornables', 'codigo_sri' => '06', 'activo' => true],
            ['codigo' => 'IM', 'descripcion' => 'Impuesto a los Vehículos Motorizados', 'codigo_sri' => '07', 'activo' => true],
            ['codigo' => 'IT', 'descripcion' => 'Impuesto a las Tierras Rurales', 'codigo_sri' => '08', 'activo' => true],
            ['codigo' => 'AE', 'descripcion' => 'Impuesto a los Activos en el Exterior', 'codigo_sri' => '09', 'activo' => true],
            ['codigo' => 'HD', 'descripcion' => 'Impuesto a la Renta sobre Ingresos Provenientes de Herencias, Legados y Donaciones', 'codigo_sri' => '10', 'activo' => true],
            ['codigo' => 'PC', 'descripcion' => 'Patentes de Conservación para Concesión Minera', 'codigo_sri' => '11', 'activo' => true],
            ['codigo' => 'RM', 'descripcion' => 'Regalías a la Actividad Minera', 'codigo_sri' => '12', 'activo' => true],
            ['codigo' => 'CC', 'descripcion' => 'Contribución Destinada al Financiamiento de la Atención Integral del Cáncer', 'codigo_sri' => '13', 'activo' => true],
            ['codigo' => 'CS', 'descripcion' => 'Contribución Solidaria', 'codigo_sri' => '14', 'activo' => true],
            ['codigo' => 'CT', 'descripcion' => 'Contribuciones Temporales al Patrimonio', 'codigo_sri' => '15', 'activo' => true],
            ['codigo' => 'RA', 'descripcion' => 'Régimen Impositivo Voluntario, Único y Temporal para la Regularización de Activos en el Exterior', 'codigo_sri' => '16', 'activo' => true],
            ['codigo' => 'TS', 'descripcion' => 'Contribución Temporal de Seguridad (CTS)', 'codigo_sri' => '17', 'activo' => true],
            ['codigo' => 'TB', 'descripcion' => 'Contribución Temporal sobre las Utilidades de los Bancos y Cooperativas de Ahorro y Crédito', 'codigo_sri' => '18', 'activo' => true],
        ];

        foreach ($tiposImpuestos as $tipoImpuesto) {
            DB::table('tipos_impuestos')->insert([
                'codigo' => $tipoImpuesto['codigo'],
                'descripcion' => $tipoImpuesto['descripcion'],
                'codigo_sri' => $tipoImpuesto['codigo_sri'],
                'activo' => $tipoImpuesto['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
