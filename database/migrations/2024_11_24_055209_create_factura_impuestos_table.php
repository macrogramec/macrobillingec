<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('tipo_impuesto_codigo', 2);
            $table->string('tarifa_codigo', 4);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('porcentaje', 8, 2);
            $table->decimal('valor_especifico', 12, 6)->nullable();
            $table->decimal('valor', 14, 2);
            $table->timestamps();

            $table->foreign('tipo_impuesto_codigo')
                ->references('codigo')
                ->on('tipos_impuestos');

            // Usar un nombre más corto para el índice
            $table->index(
                ['factura_id', 'tipo_impuesto_codigo', 'tarifa_codigo'],
                'fact_imp_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_impuestos');
    }
};
