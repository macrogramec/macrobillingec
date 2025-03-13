<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 13)->unique();
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->text('direccion_matriz');
            $table->boolean('obligado_contabilidad')->default(false);
            $table->string('contribuyente_especial')->nullable();
            $table->enum('ambiente', ['produccion', 'pruebas'])->default('pruebas');
            $table->enum('tipo_emision', ['normal', 'contingencia'])->default('normal');
            $table->json('correos_notificacion')->nullable();
            $table->text('logo')->nullable();
            $table->boolean('regimen_microempresas')->default(false);
            $table->string('agente_retencion')->nullable();
            $table->boolean('rimpe')->default(false);

            // Datos firma electrónica
            $table->text('firma_electronica')->nullable();
            $table->string('clave_firma')->nullable();
            $table->timestamp('fecha_vencimiento_firma')->nullable();
            $table->string('usuario_macrobilling');

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
