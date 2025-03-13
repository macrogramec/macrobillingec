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
        Schema::create('retencion_beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retencion_id')->constrained('retenciones')->onDelete('cascade');
            $table->string('tipo_beneficiario', 2);
            $table->string('identificacion', 20);
            $table->string('razon_social');
            $table->string('tipo_cuenta')->nullable();
            $table->string('numero_cuenta')->nullable();
            $table->string('banco')->nullable();
            $table->decimal('porcentaje_participacion', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retencion_beneficiarios');
    }
};
