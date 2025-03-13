<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CondicionesImpuestosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $condiciones = [
            [
                'tarifa_impuesto_id' => 3, // IC Vehículos
                'tipo_condicion' => 'PRECIO_MAYOR_QUE',
                'parametros' => json_encode([
                    'valor' => 70000.00,
                    'incluye_impuestos' => true
                ]),
                'validaciones' => json_encode([
                    'campo' => 'precio_unitario',
                    'operador' => '>',
                    'valor' => 70000.00
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tarifa_impuesto_id' => 6, // IC Bebidas Gaseosas
                'tipo_condicion' => 'CONTENIDO_AZUCAR',
                'parametros' => json_encode([
                    'valor_maximo' => 25,
                    'unidad' => 'g/l'
                ]),
                'validaciones' => json_encode([
                    'campo' => 'contenido_azucar',
                    'operador' => '<=',
                    'valor' => 25
                ]),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('condiciones_impuestos')->insert($condiciones);
    }
}
