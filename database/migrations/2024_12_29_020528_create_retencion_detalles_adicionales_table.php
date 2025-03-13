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
        Schema::create('retencion_detalles_adicionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retencion_id')->constrained('retenciones')->onDelete('cascade');
            $table->string('nombre', 300);
            $table->text('valor');
            $table->integer('orden')->default(0);
            $table->string('version', 5)->nullable();
            $table->string('usuario_creacion')->nullable();
            $table->string('ip_creacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retencion_detalles_adicionales');
    }
};
