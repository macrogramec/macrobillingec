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
        Schema::create('liquidacion_compra_impuestos', function (Blueprint $table) {
            $table->id();

            // Relación con detalle usando nombre corto para la llave foránea
            $table->foreignId('liquidacion_compra_detalle_id')
                ->constrained('liquidacion_compra_detalles')
                ->onDelete('cascade')
                ->name('lc_imp_detalle_fk');

            // Datos del impuesto
            $table->string('codigo', 1);
            $table->string('codigo_porcentaje', 4);
            $table->decimal('tarifa', 5, 2);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('valor', 14, 2);

            // Control y versiones
            $table->string('version', 5)->comment('Versión del documento');
            $table->boolean('activo')->default(true);

            // Auditoría
            $table->string('usuario_creacion')->nullable();
            $table->string('usuario_modificacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices con nombres cortos
            $table->index(['liquidacion_compra_detalle_id', 'codigo'], 'lc_imp_det_cod_idx');
            $table->index(['codigo', 'codigo_porcentaje'], 'lc_imp_codigos_idx');
            $table->index('version', 'lc_imp_version_idx');
            $table->index('activo', 'lc_imp_activo_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_impuestos');
    }
};
