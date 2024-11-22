<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_detalles_adicionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            
            // Campos b�sicos clave-valor
            $table->string('nombre', 300); // campo "clave" seg�n SRI
            $table->text('valor'); // campo "valor" seg�n SRI
            
            // Control de orden y versi�n
            $table->integer('orden')->default(0); // Para mantener orden en el XML
            $table->string('version', 5); // Versi�n del XML donde se usa
            
            // Campos de auditor�a
            $table->string('usuario_creacion')->nullable();
            $table->string('ip_creacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // �ndices
            $table->index(['factura_id', 'nombre']);
            $table->index(['factura_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalles_adicionales');
    }
};