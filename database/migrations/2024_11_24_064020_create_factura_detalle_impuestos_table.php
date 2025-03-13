<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_detalle_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_detalle_id')->constrained('factura_detalles')->onDelete('cascade');
            $table->string('codigo', 2);
            $table->string('codigo_porcentaje', 4);
            $table->decimal('tarifa', 14, 2);
            $table->decimal('base_imponible', 14, 2);
            $table->decimal('valor', 14, 2);
            $table->string('version', 5);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['factura_detalle_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalle_impuestos');
    }
};
