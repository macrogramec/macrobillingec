<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_estados', function (Blueprint $table) {
            $table->id();

            // Relación con la nota de crédito
            $table->foreignId('nota_credito_id')->constrained('notas_credito')->onDelete('cascade')
                ->comment('ID de la nota de crédito a la que pertenece este estado');

            // Estados principales del documento
            $table->string('estado_actual', 20)
                ->comment('Estados posibles: CREADA, FIRMADA, ENVIADA, AUTORIZADA, RECHAZADA, ANULADA');
            $table->string('estado_sri', 20)->nullable()
                ->comment('Estados del SRI: RECIBIDA, EN_PROCESO, AUTORIZADA, RECHAZADA, ANULADA');

            // Control de firma electrónica
            $table->datetime('fecha_firma')->nullable()
                ->comment('Fecha y hora en que se firmó el documento');
            $table->boolean('firmado_exitoso')->nullable()
                ->comment('Indica si el proceso de firma fue exitoso');
            $table->text('error_firma')->nullable()
                ->comment('Mensaje de error en caso de fallo en la firma');
            $table->string('certificado_firma')->nullable()
                ->comment('Nombre o identificador del certificado usado para firmar');

            // Control de envío y recepción SRI
            $table->datetime('fecha_envio_sri')->nullable()
                ->comment('Fecha y hora del envío al SRI');
            $table->string('codigo_envio_sri')->nullable()
                ->comment('Código de recepción asignado por el SRI');
            $table->text('mensaje_envio_sri')->nullable()
                ->comment('Mensaje de respuesta del SRI al envío');
            $table->boolean('envio_exitoso')->nullable()
                ->comment('Indica si el envío al SRI fue exitoso');

            // Respuesta de recepción SRI
            $table->datetime('fecha_recepcion_sri')->nullable()
                ->comment('Fecha y hora de la recepción por el SRI');
            $table->string('estado_recepcion_sri')->nullable()
                ->comment('Estado de recepción asignado por el SRI');
            $table->json('respuesta_recepcion_sri')->nullable()
                ->comment('Respuesta completa del SRI en la recepción');
            $table->text('observaciones_recepcion')->nullable()
                ->comment('Observaciones adicionales de la recepción');

            // Autorización SRI
            $table->datetime('fecha_autorizacion')->nullable()
                ->comment('Fecha y hora de autorización por el SRI');
            $table->string('numero_autorizacion', 49)->nullable()
                ->comment('Número de autorización otorgado por el SRI');
            $table->string('ambiente_autorizacion', 1)->nullable()
                ->comment('Ambiente en que fue autorizado: 1=Pruebas, 2=Producción');
            $table->json('respuesta_autorizacion_sri')->nullable()
                ->comment('Respuesta completa de autorización del SRI');

            // Control de errores y advertencias
            $table->json('errores')->nullable()
                ->comment('Registro de errores ocurridos en el proceso');
            $table->json('advertencias')->nullable()
                ->comment('Advertencias que no impiden la autorización');

            // Control de reenvíos
            $table->integer('numero_intentos')->default(0)
                ->comment('Número de intentos de envío realizados');
            $table->datetime('ultimo_intento')->nullable()
                ->comment('Fecha y hora del último intento de envío');
            $table->datetime('proximo_intento')->nullable()
                ->comment('Fecha y hora programada para el próximo intento');
            $table->boolean('requiere_reenvio')->default(false)
                ->comment('Indica si el documento requiere ser reenviado');
            $table->text('motivo_reenvio')->nullable()
                ->comment('Motivo por el que se requiere reenvío');

            // Campos de auditoría
            $table->string('ip_origen')->nullable()
                ->comment('IP desde donde se realizó el proceso');
            $table->string('usuario_proceso')->nullable()
                ->comment('Usuario que ejecutó el proceso');
            $table->json('historial_cambios')->nullable()
                ->comment('Registro histórico de cambios de estado');

            // Control de procesamiento asíncrono
            $table->string('job_id')->nullable()
                ->comment('ID del trabajo en cola de procesamiento');
            $table->string('job_status')->nullable()
                ->comment('Estado del trabajo en cola');
            $table->text('job_error')->nullable()
                ->comment('Error del trabajo en cola si existe');

            // Timestamps y softDeletes
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index('estado_actual');
            $table->index('estado_sri');
            $table->index(['nota_credito_id', 'estado_actual']);
            $table->index('numero_autorizacion');
            $table->index('fecha_autorizacion');
            $table->comment('Almacena el historial y control de estados de las notas de crédito electrónicas');

        });

        // Comentario de la tabla
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_estados');
    }
};
