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
        Schema::create('retencion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retencion_id')->constrained('retenciones')->onDelete('cascade');
            $table->integer('linea');
            $table->string('codigo', 10);
            $table->string('tipo_impuesto', 5);
            // Documento sustento
            $table->string('cod_doc_sustento', 2);
            $table->string('num_doc_sustento');
            $table->string('fecha_emision_doc_sustento');

            // Valores de retención
            $table->foreignId('codigo_retencion_id')->constrained('codigos_retencion');
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('porcentaje_retener', 5, 2);
            $table->decimal('valor_retenido', 14, 2);

            // Campos para dividendos
            $table->decimal('utilidad_antes_ir')->nullable();
            $table->decimal('impuesto_renta_sociedad')->nullable();
            $table->decimal('utilidad_efectiva')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retencion_detalles');
    }
};
