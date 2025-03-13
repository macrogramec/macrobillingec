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
        Schema::create('liquidacion_compra_detalles_adicionales', function (Blueprint $table) {
            $table->id();

            // Relación con liquidación o detalle usando nombres cortos para llaves foráneas
            $table->foreignId('liquidacion_compra_id')
                ->nullable()
                ->constrained('liquidaciones_compra')
                ->onDelete('cascade')
                ->name('lc_det_adic_lc_fk');

            $table->foreignId('liquidacion_compra_detalle_id')
                ->nullable()
                ->constrained('liquidacion_compra_detalles')
                ->onDelete('cascade')
                ->name('lc_det_adic_det_fk');

            // Información adicional
            $table->string('nombre', 300)->comment('Nombre del campo adicional');
            $table->string('valor', 300)->comment('Valor del campo adicional');
            $table->integer('orden')->default(0)->comment('Orden de presentación');

            // Control y versiones
            $table->string('version', 5)->comment('Versión del documento');
            $table->boolean('activo')->default(true);

            // Auditoría completa
            $table->string('usuario_creacion')->nullable();
            $table->string('ip_creacion')->nullable();
            $table->string('usuario_modificacion')->nullable();
            $table->string('ip_modificacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices con nombres cortos
            $table->index('nombre', 'lc_det_adic_nombre_idx');
            $table->index(['liquidacion_compra_id', 'orden'], 'lc_det_adic_lc_orden_idx');
            $table->index(['liquidacion_compra_detalle_id', 'orden'], 'lc_det_adic_det_orden_idx');
            $table->index('activo', 'lc_det_adic_activo_idx');
            $table->index('version', 'lc_det_adic_version_idx');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_detalles_adicionales');
    }
};
