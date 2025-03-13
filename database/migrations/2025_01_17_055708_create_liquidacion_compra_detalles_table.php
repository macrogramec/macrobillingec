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
        Schema::create('liquidacion_compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_compra_id')->constrained('liquidaciones_compra')->onDelete('cascade');

            // Campos obligatorios
            $table->integer('linea')->comment('Número de línea/secuencial del detalle');
            $table->string('codigo_principal', 25);
            $table->string('codigo_auxiliar', 25)->nullable();
            $table->string('descripcion', 300);

            // Campos numéricos con precisión extendida para v1.1.0
            $table->decimal('cantidad', 14, 6)->comment('6 decimales en v1.1.0');
            $table->decimal('precio_unitario', 14, 6)->comment('6 decimales en v1.1.0');
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('precio_total_sin_impuesto', 14, 2);

            // Campos de comercio exterior
            $table->string('unidad_medida', 50)->nullable();
            $table->decimal('precio_sin_subsidio', 14, 6)->nullable();
            $table->string('codigo_partida_arancelaria', 20)->nullable();
            $table->decimal('precio_referencial_unitario', 14, 6)->nullable();
            $table->string('pais_origen', 3)->nullable();
            $table->string('pais_adquisicion', 3)->nullable();

            // Control y versión
            $table->json('detalles_adicionales')->nullable();
            $table->string('version', 5)->comment('Versión del detalle (1.0.0 o 1.1.0)');

            // Auditoría
            $table->string('usuario_creacion')->nullable();
            $table->string('usuario_modificacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo_principal');
            $table->index(['liquidacion_compra_id', 'linea']);
            $table->index(['liquidacion_compra_id', 'codigo_principal'], 'lc_det_cod_index');
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_detalles');
    }
};
