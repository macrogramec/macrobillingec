<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FormasPagoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $formasPago = [
            [
                'codigo' => '01',
                'descripcion' => 'Sin utilizacion del sistema financiero',
                'requiere_plazo' => false,
                'requiere_banco' => false,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '15',
                'descripcion' => 'Compensacion de deudas',
                'requiere_plazo' => true,
                'requiere_banco' => false,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '16',
                'descripcion' => 'Tarjeta de debito',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '17',
                'descripcion' => 'Dinero electronico',
                'requiere_plazo' => false,
                'requiere_banco' => false,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '18',
                'descripcion' => 'Tarjeta prepago',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '19',
                'descripcion' => 'Tarjeta de credito',
                'requiere_plazo' => true,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '20',
                'descripcion' => 'Otros con utilizacion del sistema financiero',
                'requiere_plazo' => true,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '21',
                'descripcion' => 'Endoso de titulos',
                'requiere_plazo' => true,
                'requiere_banco' => false,
                'activo' => true,
                'version_desde' => '1.0.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '22',
                'descripcion' => 'Giro',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '2.1.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '23',
                'descripcion' => 'Deposito en cuenta',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '2.1.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '24',
                'descripcion' => 'Transferencia bancaria',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => true,
                'version_desde' => '2.1.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '25',
                'descripcion' => 'Tarjeta de regalo o vale',
                'requiere_plazo' => false,
                'requiere_banco' => false,
                'activo' => true,
                'version_desde' => '2.1.0',
                'version_hasta' => null,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '02',
                'descripcion' => 'Cheque propio',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => false,
                'version_desde' => '1.0.0',
                'version_hasta' => '1.1.0',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '03',
                'descripcion' => 'Cheque certificado',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => false,
                'version_desde' => '1.0.0',
                'version_hasta' => '1.1.0',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '04',
                'descripcion' => 'Cheque de gerencia',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => false,
                'version_desde' => '1.0.0',
                'version_hasta' => '1.1.0',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'codigo' => '05',
                'descripcion' => 'Cheque del exterior',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'activo' => false,
                'version_desde' => '1.0.0',
                'version_hasta' => '1.1.0',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];

        // Insertar todas las formas de pago
        DB::table('formas_pago')->insert($formasPago);
    }
}