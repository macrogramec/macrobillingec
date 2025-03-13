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
        Schema::create('historial_tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarifa_impuesto_id')->constrained('tarifas_impuestos');
            $table->decimal('porcentaje_anterior', 8, 2);
            $table->decimal('porcentaje_nuevo', 8, 2);
            $table->decimal('valor_especifico_anterior', 12, 6)->nullable();
            $table->decimal('valor_especifico_nuevo', 12, 6)->nullable();
            $table->string('motivo');
            $table->string('documento_respaldo')->nullable();
            $table->string('usuario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_tarifas');
    }
};
