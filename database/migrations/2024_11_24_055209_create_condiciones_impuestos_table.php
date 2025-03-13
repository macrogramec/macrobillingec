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
        Schema::create('condiciones_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarifa_impuesto_id')->constrained('tarifas_impuestos');
            $table->string('tipo_condicion');
            $table->json('parametros');
            $table->json('validaciones');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condiciones_impuestos');
    }
};
