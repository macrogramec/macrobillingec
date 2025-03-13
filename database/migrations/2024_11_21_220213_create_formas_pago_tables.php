<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla catálogo de formas de pago
        Schema::create('formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 2);
            $table->string('descripcion', 100);
            $table->boolean('requiere_plazo')->default(false);
            $table->boolean('requiere_banco')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('version_desde', 5);
            $table->string('version_hasta', 5)->nullable();
            $table->timestamps();

            $table->index('codigo');
            $table->index('activo');
            $table->unique('codigo');
        });

        // Configurar charset para descripción
        DB::statement('ALTER TABLE formas_pago MODIFY descripcion VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Tabla para los pagos de cada factura
        Schema::create('factura_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('formaPago', 2); // Código de forma de pago según SRI
            $table->decimal('total', 14, 2); // Valor del pago
            $table->integer('plazo')->nullable(); // Plazo en días/meses según unidadTiempo
            $table->string('unidadTiempo', 10)->default('dias'); // dias, meses, años

            // Campos específicos v2.1.0
            $table->string('institucionFinanciera', 100)->nullable();
            $table->string('numeroCuenta', 50)->nullable();
            $table->string('numeroTarjeta', 20)->nullable(); // Últimos 4 dígitos
            $table->string('propietarioTarjeta', 100)->nullable();

            // Campos de control
            $table->string('version', 5); // Versión del XML para la que aplica
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('formaPago');
            $table->index(['factura_id', 'formaPago']);
        });

        // Configurar charset para toda la tabla factura_pagos
        DB::statement('ALTER TABLE factura_pagos CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Insertar formas de pago iniciales
        $this->insertarFormasPago();
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_pagos');
        Schema::dropIfExists('formas_pago');
    }

    private function insertarFormasPago(): void
    {
        $formasPago = [
            [
                'codigo' => '01',
                'descripcion' => 'Sin utilización del sistema financiero',
                'requiere_plazo' => false,
                'requiere_banco' => false,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '15',
                'descripcion' => 'Compensación de deudas',
                'requiere_plazo' => false,
                'requiere_banco' => false,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '16',
                'descripcion' => 'Tarjeta de débito',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '17',
                'descripcion' => 'Tarjeta de crédito',
                'requiere_plazo' => true,
                'requiere_banco' => true,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '18',
                'descripcion' => 'Tarjeta prepago',
                'requiere_plazo' => false,
                'requiere_banco' => true,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '19',
                'descripcion' => 'Tarjeta de crédito',
                'requiere_plazo' => true,
                'requiere_banco' => true,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '20',
                'descripcion' => 'Otros con utilización del sistema financiero',
                'requiere_plazo' => true,
                'requiere_banco' => true,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo' => '21',
                'descripcion' => 'Endoso de títulos',
                'requiere_plazo' => true,
                'requiere_banco' => false,
                'version_desde' => '1.0.0',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('formas_pago')->insert($formasPago);
    }
};
