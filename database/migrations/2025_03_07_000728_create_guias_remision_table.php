<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guias_remision', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('estado', 20);
            $table->string('version', 10);
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('establecimiento_id')->constrained('establecimientos');
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');
            $table->string('ambiente', 1); // 1: Pruebas, 2: Producción
            $table->string('tipo_emision', 1)->default('1'); // 1: Normal, 2: Indisponibilidad
            $table->string('razonSocial', 300);
            $table->string('nombreComercial', 300)->nullable();
            $table->string('ruc', 13);
            $table->string('claveAcceso', 49);
            $table->string('codDoc', 2)->default('06');
            $table->string('estab', 3);
            $table->string('ptoEmi', 3);
            $table->string('secuencial', 9);
            $table->string('dirMatriz', 300);
            $table->string('dirEstablecimiento', 300)->nullable();
            $table->string('dirPartida', 300);
            $table->string('razonSocialTransportista', 300);
            $table->string('tipoIdentificacionTransportista', 2);
            $table->string('rucTransportista', 20);
            $table->string('rise', 40)->nullable();
            $table->string('obligadoContabilidad', 2)->nullable();
            $table->string('contribuyenteEspecial', 13)->nullable();
            $table->date('fechaIniTransporte');
            $table->date('fechaFinTransporte');
            $table->string('placa', 20);
            $table->boolean('procesadoSri')->default(false);
            $table->dateTime('fechaAutorizacion')->nullable();
            $table->string('numeroAutorizacion', 49)->nullable();
            $table->string('ambienteAutorizacion', 1)->nullable();
            $table->json('infoAdicional')->nullable();
            $table->string('version_actual', 10)->nullable();
            $table->json('historial_cambios')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guias_remision');
    }
};
