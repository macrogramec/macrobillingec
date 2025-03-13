<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guia_remision_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_destinatario_id')->constrained('guia_remision_destinatarios')->onDelete('cascade');
            $table->string('codigoInterno', 25)->nullable();
            $table->string('codigoAdicional', 25)->nullable();
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 18, 6);
            $table->json('detallesAdicionales')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guia_remision_detalles');
    }
};
