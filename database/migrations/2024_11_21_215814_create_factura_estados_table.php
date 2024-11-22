<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            
            // Estados principales del documento
            $table->string('estado_actual', 20); // CREADA, FIRMADA, ENVIADA, AUTORIZADA, RECHAZADA, ANULADA
            $table->string('estado_sri', 20)->nullable(); // RECIBIDA, EN_PROCESO, AUTORIZADA, RECHAZADA, ANULADA
            
            // Control de firma electrónica
            $table->datetime('fecha_firma')->nullable();
            $table->boolean('firmado_exitoso')->nullable();
            $table->text('error_firma')->nullable();
            $table->string('certificado_firma')->nullable(); // Nombre del certificado usado
            
            // Control de envío y recepción SRI
            $table->datetime('fecha_envio_sri')->nullable();
            $table->string('codigo_envio_sri')->nullable(); // Código de recepción inicial
            $table->text('mensaje_envio_sri')->nullable();
            $table->boolean('envio_exitoso')->nullable();
            
            // Respuesta de recepción SRI
            $table->datetime('fecha_recepcion_sri')->nullable();
            $table->string('estado_recepcion_sri')->nullable();
            $table->json('respuesta_recepcion_sri')->nullable();
            $table->text('observaciones_recepcion')->nullable();
            
            // Autorización SRI
            $table->datetime('fecha_autorizacion')->nullable();
            $table->string('numero_autorizacion', 49)->nullable();
            $table->string('ambiente_autorizacion', 1)->nullable();
            $table->json('respuesta_autorizacion_sri')->nullable();
            $table->text('observaciones_autorizacion')->nullable();
            
            // Control de errores y advertencias
            $table->json('errores')->nullable(); // {codigo, mensaje, informacionAdicional, tipo}
            $table->json('advertencias')->nullable(); // Warnings que no impiden la autorización
            
            // Control de contingencia
            $table->boolean('proceso_contingencia')->default(false);
            $table->datetime('fecha_inicio_contingencia')->nullable();
            $table->datetime('fecha_fin_contingencia')->nullable();
            $table->text('motivo_contingencia')->nullable();
            
            // Control de anulación
            $table->boolean('anulado')->default(false);
            $table->datetime('fecha_anulacion')->nullable();
            $table->string('motivo_anulacion')->nullable();
            $table->json('respuesta_anulacion_sri')->nullable();
            
            // Control de reenvíos
            $table->integer('numero_intentos')->default(0);
            $table->datetime('ultimo_intento')->nullable();
            $table->datetime('proximo_intento')->nullable();
            $table->boolean('requiere_reenvio')->default(false);
            $table->text('motivo_reenvio')->nullable();
            
            // Campos de auditoría internos
            $table->string('ip_origen')->nullable();
            $table->string('usuario_proceso')->nullable();
            $table->json('historial_cambios')->nullable();
            
            // Control de notificaciones
            $table->boolean('notificacion_enviada')->default(false);
            $table->datetime('fecha_notificacion')->nullable();
            $table->string('email_notificacion')->nullable();
            $table->text('error_notificacion')->nullable();
            
            // Control de procesamiento asíncrono
            $table->string('job_id')->nullable(); // ID del trabajo en cola
            $table->string('job_status')->nullable(); // Estado del trabajo en cola
            $table->text('job_error')->nullable(); // Error del trabajo en cola
            
            // Timestamps y softDeletes
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('estado_actual');
            $table->index('estado_sri');
            $table->index(['factura_id', 'estado_actual']);
            $table->index('numero_autorizacion');
            $table->index('fecha_autorizacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_estados');
    }
};