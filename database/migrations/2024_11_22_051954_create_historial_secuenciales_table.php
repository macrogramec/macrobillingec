<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_secuenciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');
            $table->string('tipo_comprobante');
            $table->bigInteger('secuencial_anterior');
            $table->bigInteger('secuencial_nuevo');
            $table->text('motivo');

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_secuenciales');
    }
};
