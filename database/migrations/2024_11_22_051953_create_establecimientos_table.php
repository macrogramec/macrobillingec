<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('codigo', 3);
            $table->string('direccion');
            $table->string('nombre_comercial')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->enum('ambiente', ['produccion', 'pruebas'])->default('pruebas');
            $table->json('correos_establecimiento')->nullable();

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['empresa_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
