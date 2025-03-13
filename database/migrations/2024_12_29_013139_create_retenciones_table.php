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
        Schema::create('retenciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('empresa_id')->constrained();
            $table->foreignId('establecimiento_id')->constrained();
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');

            // Campos de control
            $table->string('estado', 20);
            $table->string('version', 5);
            $table->string('ambiente', 1);
            $table->string('tipo_emision', 1);

            // Campos del emisor
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('ruc', 13);
            $table->string('clave_acceso', 49)->unique();
            $table->string('cod_doc', 2)->default('07');
            $table->string('estab', 3);
            $table->string('pto_emi', 3);
            $table->string('secuencial', 9);
            $table->string('dir_matriz');
            $table->string('obligadoContabilidad',2)->default('NO');

            // Campos específicos de retención
            $table->string('periodo_fiscal', 7); // formato: MM/YYYY
            $table->string('tipo_identificacion_sujeto', 2);
            $table->string('razon_social_sujeto');
            $table->date('fechaEmision')
                ->comment('Fecha de emisión de la retencion');
            $table->string('identificacion_sujeto', 20);
            $table->string('tipo_sujeto', 20); // sociedad, persona_natural
            $table->string('regimen_sujeto', 20)->nullable(); // rimpe, general
            $table->string('email', 250)->nullable(); // rimpe, general
            $table->enum('tipo_retencion', ['normal', 'dividendos', 'participaciones']);

            // Totales
            $table->decimal('total_retenido', 14, 2);

            // Campos para dividendos/participaciones
            $table->string('ejercicio_fiscal', 4)->nullable();
            $table->date('fecha_pago')->nullable();
            $table->decimal('valor_pago', 14, 2)->nullable();
            $table->string('beneficiario_tipo', 2)->nullable();
            $table->string('beneficiario_id', 20)->nullable();
            $table->string('beneficiario_razon_social')->nullable();
            $table->decimal('ingreso_gravado', 14, 2)->nullable();

            // Control de versiones y timestamps
            $table->json('info_adicional')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['empresa_id', 'estado']);
            $table->index(['identificacion_sujeto', 'periodo_fiscal']);
            $table->index('clave_acceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retenciones');
    }
};
