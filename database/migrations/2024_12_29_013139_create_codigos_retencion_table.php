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
        Schema::create('codigos_retencion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_impuesto', 2); // 'IR', 'IV'
            $table->string('codigo', 5);
            $table->string('concepto', 300);
            $table->decimal('porcentaje', 5, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->enum('tipo_persona', ['natural', 'sociedad'])->nullable();
            $table->enum('tipo_regimen', ['rimpe', 'general', 'especial'])->nullable();
            $table->enum('categoria', ['normal', 'dividendos', 'participaciones'])->default('normal');
            $table->json('validaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_retencion');
    }
};
