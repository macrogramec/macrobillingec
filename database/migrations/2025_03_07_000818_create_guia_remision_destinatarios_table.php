<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guia_remision_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guia_remision_id')->constrained('guias_remision')->onDelete('cascade');
            $table->string('identificacionDestinatario', 20);
            $table->string('razonSocialDestinatario', 300);
            $table->string('dirDestinatario', 300);
            $table->string('motivoTraslado', 300);
            $table->string('docAduaneroUnico', 20)->nullable();
            $table->string('codEstabDestino', 3)->nullable();
            $table->string('ruta', 300)->nullable();
            $table->string('codDocSustento', 2)->nullable();
            $table->string('numDocSustento', 15)->nullable();
            $table->string('numAutDocSustento', 49)->nullable();
            $table->date('fechaEmisionDocSustento')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guia_remision_destinatarios');
    }
};
