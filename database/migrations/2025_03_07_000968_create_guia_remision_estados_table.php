<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guia_remision_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_id')->constrained('guias_remision')->onDelete('cascade');
            $table->string('estado_actual', 20);
            $table->string('estado_sri', 20)->nullable();
            $table->dateTime('fecha_firma')->nullable();
            $table->boolean('firmado_exitoso')->nullable();
            $table->text('error_firma')->nullable();
            $table->string('certificado_firma')->nullable();
            $table->dateTime('fecha_envio_sri')->nullable();
            $table->string('codigo_envio_sri')->nullable();
            $table->text('mensaje_envio_sri')->nullable();
            $table->boolean('envio_exitoso')->nullable();
            $table->dateTime('fecha_recepcion_sri')->nullable();
            $table->string('estado_recepcion_sri')->nullable();
            $table->json('respuesta_recepcion_sri')->nullable();
            $table->text('observaciones_recepcion')->nullable();
            $table->dateTime('fecha_autorizacion')->nullable();
            $table->string('numero_autorizacion', 49)->nullable();
            $table->string('ambiente_autorizacion', 1)->nullable();
            $table->json('respuesta_autorizacion_sri')->nullable();
            $table->text('observaciones_autorizacion')->nullable();
            $table->json('errores')->nullable();
            $table->json('advertencias')->nullable();
            $table->boolean('proceso_contingencia')->default(false);
            $table->dateTime('fecha_inicio_contingencia')->nullable();
            $table->dateTime('fecha_fin_contingencia')->nullable();
            $table->text('motivo_contingencia')->nullable();
            $table->boolean('anulado')->default(false);
            $table->dateTime('fecha_anulacion')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->json('respuesta_anulacion_sri')->nullable();
            $table->integer('numero_intentos')->default(0);
            $table->dateTime('ultimo_intento')->nullable();
            $table->dateTime('proximo_intento')->nullable();
            $table->boolean('requiere_reenvio')->default(false);
            $table->text('motivo_reenvio')->nullable();
            $table->string('ip_origen')->nullable();
            $table->string('usuario_proceso')->nullable();
            $table->json('historial_cambios')->nullable();
            $table->boolean('notificacion_enviada')->default(false);
            $table->dateTime('fecha_notificacion')->nullable();
            $table->string('email_notificacion')->nullable();
            $table->text('error_notificacion')->nullable();
            $table->string('job_id')->nullable();
            $table->string('job_status')->nullable();
            $table->text('job_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guia_remision_estados');
    }
};
