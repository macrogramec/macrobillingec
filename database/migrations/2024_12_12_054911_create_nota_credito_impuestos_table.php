<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_impuestos', function (Blueprint $table) {
            $table->id();

            // Relación con nota de crédito
            $table->foreignId('nota_credito_id')->constrained('notas_credito')->onDelete('cascade')
                ->comment('ID de la nota de crédito a la que pertenece este impuesto');

            // Datos del impuesto según el SRI
            $table->string('tipo_impuesto_codigo', 2)
                ->comment('Código del tipo de impuesto (IV=IVA, IC=ICE, etc)');

            $table->string('tarifa_codigo', 4)
                ->comment('Código de la tarifa según catálogo SRI');

            // Valores y cálculos
            $table->decimal('base_imponible', 14, 2)
                ->comment('Base imponible para el cálculo del impuesto');

            $table->decimal('porcentaje', 8, 2)
                ->comment('Porcentaje aplicado al impuesto (Ej: 15.00 para IVA 15%)');

            $table->decimal('valor_especifico', 12, 6)
                ->nullable()
                ->comment('Valor específico para impuestos de monto fijo');

            $table->decimal('valor', 14, 2)
                ->comment('Valor calculado del impuesto');

            // Campos para control de devolución/reversión
            $table->decimal('valor_devuelto', 14, 2)
                ->default(0)
                ->comment('Valor que se está devolviendo/revirtiendo del impuesto original');

            $table->boolean('impuesto_retenido')
                ->default(false)
                ->comment('Indica si el impuesto fue retenido en la transacción original');

            // Referencia a factura original
            $table->foreignId('factura_impuesto_id')
                ->nullable()
                ->constrained('factura_impuestos')
                ->comment('ID del impuesto en la factura original, si aplica');

            // Control de versión y validez
            $table->string('version', 5)
                ->comment('Versión del formato XML: 1.0.0, 1.1.0, 2.0.0, 2.1.0');

            $table->boolean('activo')
                ->default(true)
                ->comment('Indica si el registro está activo o ha sido anulado');

            // Campos de auditoría
            $table->string('usuario_creacion')
                ->nullable()
                ->comment('Usuario que creó el registro');

            $table->string('usuario_modificacion')
                ->nullable()
                ->comment('Usuario que realizó la última modificación');

            // Control de tiempo y eliminación suave
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['nota_credito_id', 'tipo_impuesto_codigo'], 'idx_nc_impuesto');

            $table->index('tarifa_codigo');
            $table->index('factura_impuesto_id');
            $table->index(['tipo_impuesto_codigo', 'tarifa_codigo'], 'idx_impuesto_tipo_tarifa');
            $table->index('activo');
            $table->comment('Almacena los impuestos aplicados a las notas de crédito electrónicas');

        });

        // Comentario de la tabla
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_impuestos');
    }
};
