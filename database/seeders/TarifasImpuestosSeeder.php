<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TarifasImpuestosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tarifas = [

            // ICE Específicos
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC01',
                'codigo_sri' => '3011',
                'descripcion' => 'Productos del tabaco y sucedáneos del tabaco',
                'porcentaje' => 150,
                'tipo_calculo' => 'PORCENTAJE',
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC02',
                'codigo_sri' => '3023',
                'descripcion' => 'Bebidas alcohólicas, incluida la cerveza artesanal',
                'porcentaje' => 75,
                'tipo_calculo' => 'PORCENTAJE',
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC03',
                'codigo_sri' => '3051',
                'descripcion' => 'Vehículos motorizados cuyo PVP superior a USD 70.000',
                'porcentaje' => 35,
                'tipo_calculo' => 'PORCENTAJE',
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC04',
                'codigo_sri' => '3072',
                'descripcion' => 'Fundas plásticas',
                'tipo_calculo' => 'ESPECIFICO',
                'valor_especifico' => 0.10,
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC05',
                'codigo_sri' => '3610',
                'descripcion' => 'Cerveza Industrial',
                'tipo_calculo' => 'MIXTO',
                'porcentaje' => 75,
                'valor_especifico' => 13.20,
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC06',
                'codigo_sri' => '3620',
                'descripcion' => 'Bebidas Gaseosas con contenido de azúcar menor o igual a 25g/litro',
                'tipo_calculo' => 'ESPECIFICO',
                'valor_especifico' => 0.18,
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
            [
                'tipo_impuesto_codigo' => 'IC',
                'codigo' => 'IC07',
                'codigo_sri' => '3640',
                'descripcion' => 'Bebidas energizantes',
                'porcentaje' => 10,
                'tipo_calculo' => 'PORCENTAJE',
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],

            // IRBPNR
            [
                'tipo_impuesto_codigo' => 'IB',
                'codigo' => 'IB01',
                'codigo_sri' => '5001',
                'descripcion' => 'Botellas plásticas no retornables',
                'tipo_calculo' => 'ESPECIFICO',
                'valor_especifico' => 0.02,
                'fecha_inicio' => '2024-01-01',
                'activo' => true
            ],
        ];

        foreach ($tarifas as $tarifa) {
            $tarifa['fecha_inicio'] = Carbon::parse($tarifa['fecha_inicio']);
            $tarifa['created_at'] = now();
            $tarifa['updated_at'] = now();
            DB::table('tarifas_impuestos')->insert($tarifa);
        }
    }
}
