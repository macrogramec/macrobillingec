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
        Schema::create('historial_codigos_retencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codigo_retencion_id')->constrained('retenciones');
            $table->decimal('porcentaje_anterior', 5, 2);
            $table->decimal('porcentaje_nuevo', 5, 2);
            $table->string('motivo');
            $table->string('documento_respaldo')->nullable();
            $table->string('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_codigos_retencion');
    }
};
