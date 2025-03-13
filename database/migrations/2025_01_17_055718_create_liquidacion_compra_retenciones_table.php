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
        Schema::create('liquidacion_compra_retenciones', function (Blueprint $table) {
            $table->id();

            // Relación con nombre corto para la llave foránea
            $table->foreignId('liquidacion_compra_id')
                ->constrained('liquidaciones_compra')
                ->onDelete('cascade')
                ->name('lc_ret_lc_fk');

            // Datos principales de la retención
            $table->string('codigo', 3)->comment('Código del tipo de retención');
            $table->string('codigo_porcentaje', 5)->comment('Código del porcentaje de retención');
            $table->decimal('tarifa', 5, 2)->comment('Porcentaje de retención');
            $table->decimal('base_imponible', 14, 2)->comment('Base para el cálculo');
            $table->decimal('valor_retenido', 14, 2)->comment('Valor retenido');

            // Campos adicionales para retenciones específicas
            $table->string('tipo_renta', 50)->nullable()->comment('Tipo de renta para retenciones de IR');
            $table->string('codigo_doctributario', 2)->nullable()->comment('Código de documento tributario');
            $table->decimal('porcentaje_parcial', 5, 2)->nullable()->comment('Porcentaje parcial para retenciones especiales');

            // Datos de ejercicio fiscal (para dividendos)
            $table->string('ejercicio_fiscal', 4)->nullable();
            $table->decimal('base_imponible_iva', 14, 2)->nullable();

            // Control y versiones
            $table->string('version', 5)->comment('Versión del documento');
            $table->boolean('activo')->default(true);

            // Auditoría
            $table->string('usuario_creacion')->nullable();
            $table->string('usuario_modificacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices con nombres cortos
            $table->index(['codigo', 'codigo_porcentaje'], 'lc_ret_codigos_idx');
            $table->index(['liquidacion_compra_id', 'codigo'], 'lc_ret_lc_cod_idx');
            $table->index('ejercicio_fiscal', 'lc_ret_ejercicio_idx');
            $table->index('activo', 'lc_ret_activo_idx');
            $table->index('version', 'lc_ret_version_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_retenciones');
    }
};
