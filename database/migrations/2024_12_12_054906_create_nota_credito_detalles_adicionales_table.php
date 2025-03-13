<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_detalles_adicionales', function (Blueprint $table) {
            $table->id();

            // Relación con la nota de crédito
            $table->foreignId('nota_credito_id')->constrained('notas_credito')->onDelete('cascade')
                ->comment('ID de la nota de crédito a la que pertenece el detalle adicional');

            // Campos principales
            $table->string('nombre', 300)
                ->comment('Nombre o identificador del campo adicional. Ejemplo: Vendedor, Bodega, etc.');

            $table->text('valor')
                ->comment('Valor o contenido del campo adicional');

            $table->integer('orden')->default(0)
                ->comment('Orden en que aparecerá el campo en el documento (0 en adelante)');

            // Control de versiones
            $table->string('version', 5)
                ->comment('Versión del formato XML en que se usa este campo: 1.0.0, 1.1.0, 2.0.0, 2.1.0');

            // Campos de auditoría
            $table->string('usuario_creacion')
                ->nullable()
                ->comment('Usuario que creó el registro');

            $table->string('ip_creacion')
                ->nullable()
                ->comment('Dirección IP desde donde se creó el registro');

            // Control de tiempo y eliminación suave
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['nota_credito_id', 'nombre']);
            $table->index(['nota_credito_id', 'version']);
            $table->index('orden');
            $table->comment('Almacena información adicional personalizada para las notas de crédito electrónicas');

        });

        // Comentario de la tabla
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_detalles_adicionales');
    }
};
