<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('liquidacion_compra_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_compra_id')->constrained('liquidaciones_compra')->onDelete('cascade');

            // Estados
            $table->string('estado_anterior', 20)->comment('CREADA, FIRMADA, ENVIADA, AUTORIZADA, RECHAZADA, ANULADA');
            $table->string('estado_actual', 20);
            $table->string('estado_sri', 20)->nullable()->comment('Estado reportado por el SRI');

            // Proceso de firma
            $table->datetime('fecha_firma')->nullable();
            $table->boolean('firmado_exitoso')->nullable();
            $table->text('error_firma')->nullable();
            $table->string('certificado_firma')->nullable();

            // Proceso SRI
            $table->datetime('fecha_envio_sri')->nullable();
            $table->string('codigo_envio_sri')->nullable();
            $table->text('mensaje_envio_sri')->nullable();
            $table->boolean('envio_exitoso')->nullable();

            // Autorización
            $table->datetime('fecha_autorizacion')->nullable();
            $table->json('respuesta_sri')->nullable();
            $table->text('observaciones')->nullable();

            // Control de reintentos
            $table->integer('numero_intentos')->default(0);
            $table->datetime('ultimo_intento')->nullable();
            $table->datetime('proximo_intento')->nullable();
            $table->boolean('requiere_reenvio')->default(false);
            $table->string('motivo_reenvio')->nullable();

            // Datos de proceso
            $table->string('ip_origen')->nullable();
            $table->string('usuario_proceso')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users');

            $table->timestamps();

            // Índices con nombres cortos
            $table->index('estado_actual', 'lc_est_actual_idx');
            $table->index('estado_sri', 'lc_est_sri_idx');
            $table->index('created_at', 'lc_est_created_idx');
            $table->index(['liquidacion_compra_id', 'created_at'], 'lc_est_lc_created_idx');
            $table->index('requiere_reenvio', 'lc_est_reenvio_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_estados');
    }
};
