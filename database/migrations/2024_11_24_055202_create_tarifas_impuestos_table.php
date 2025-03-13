<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_impuesto_codigo', 2);
            $table->string('codigo', 4);
            $table->string('codigo_sri', 4);
            $table->string('descripcion');
            $table->decimal('porcentaje', 8, 2);
            $table->enum('tipo_calculo', ['PORCENTAJE', 'ESPECIFICO', 'MIXTO']);
            $table->decimal('valor_especifico', 12, 6)->nullable();
            $table->datetime('fecha_inicio');
            $table->datetime('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('tipo_impuesto_codigo')
                ->references('codigo')
                ->on('tipos_impuestos')
                ->onDelete('restrict');

            // Usar un nombre más corto para el índice único
            $table->unique(
                ['tipo_impuesto_codigo', 'codigo', 'fecha_inicio'],
                'tarifa_impuesto_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_impuestos');
    }
};
