<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puntos_emision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('establecimientos');
            $table->string('codigo', 3);
            $table->enum('tipo_comprobante', ['01', '02', '03', '04', '05', '06', '07']);
            $table->string('comprobante')->nullable();
            $table->bigInteger('secuencial_actual')->default(1);
            $table->bigInteger('secuencial_pruebas')->default(1);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->enum('ambiente', ['produccion', 'pruebas'])->default('pruebas');
            $table->json('secuencias')->nullable();

            // Identificador externo (opcional)
            $table->string('identificador_externo')->nullable();

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['establecimiento_id', 'codigo', 'tipo_comprobante']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_emision');
    }
};
