<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla cat�logo de formas de pago
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
        DB::statement('ALTER TABLE formas_pago MODIFY descripcion VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Tabla para los pagos de cada factura
        Schema::create('factura_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('formaPago', 2); // C�digo de forma de pago seg�n SRI
            $table->decimal('total', 14, 2); // Valor del pago
            $table->integer('plazo')->nullable(); // Plazo en d�as/meses seg�n unidadTiempo
            $table->string('unidadTiempo', 10)->default('dias'); // dias, meses, a�os
            
            // Campos espec�ficos v2.1.0
            $table->string('institucionFinanciera', 100)->nullable();
            $table->string('numeroCuenta', 50)->nullable();
            $table->string('numeroTarjeta', 20)->nullable(); // �ltimos 4 d�gitos
            $table->string('propietarioTarjeta', 100)->nullable();
            
            // Campos de control
            $table->string('version', 5); // Versi�n del XML para la que aplica
            $table->timestamps();
            $table->softDeletes();

            // �ndices
            $table->index('formaPago');
            $table->index(['factura_id', 'formaPago']);
        })->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_pagos');
        Schema::dropIfExists('formas_pago');
    }
};