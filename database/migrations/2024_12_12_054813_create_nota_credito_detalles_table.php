<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nota_credito_detalles', function (Blueprint $table) {
            $table->id();

            // Relaciones y control
            $table->foreignId('nota_credito_id')->constrained('notas_credito')->onDelete('cascade')
                ->comment('ID de la nota de crédito a la que pertenece el detalle');
            $table->integer('linea')
                ->comment('Número de línea del detalle para mantener el orden');

            // Relación con factura detalle (opcional para NC manuales)
            $table->foreignId('factura_detalle_id')->nullable()->constrained('factura_detalles')
                ->comment('ID del detalle de factura original si la NC se genera desde una factura del sistema');

            // Información del producto/servicio
            $table->string('codigoPrincipal', 25)->nullable()
                ->comment('Código principal del producto/servicio');
            $table->string('codigoAuxiliar', 25)->nullable()
                ->comment('Código auxiliar del producto/servicio');
            $table->string('descripcion', 300)
                ->comment('Descripción detallada del producto/servicio');

            // Cantidades y valores
            $table->decimal('cantidad', 14, 6)
                ->comment('Cantidad del producto/servicio (hasta 6 decimales)');
            $table->decimal('precioUnitario', 14, 6)
                ->comment('Precio unitario del producto/servicio (hasta 6 decimales)');
            $table->decimal('descuento', 14, 2)
                ->comment('Valor del descuento aplicado');
            $table->decimal('precioTotalSinImpuesto', 14, 2)
                ->comment('Precio total sin impuestos ((cantidad * precioUnitario) - descuento)');

            // Campos para impuestos del detalle
            $table->string('impuesto_codigo', 1)->default('2')
                ->comment('Código del impuesto (2: IVA, 3: ICE, 5: IRBPNR)');
            $table->string('impuesto_codigoPorcentaje', 4)
                ->comment('Código del porcentaje de impuesto según catálogo del SRI');
            $table->decimal('impuesto_tarifa', 14, 2)
                ->comment('Porcentaje de impuesto aplicado');
            $table->decimal('impuesto_baseImponible', 14, 2)
                ->comment('Base imponible para el cálculo del impuesto');
            $table->decimal('impuesto_valor', 14, 2)
                ->comment('Valor calculado del impuesto');

            // Campos adicionales según versión 2.1.0 del SRI
            $table->string('unidadMedida', 50)->nullable()
                ->comment('Unidad de medida del producto/servicio');
            $table->decimal('precioUnitarioSubsidio', 14, 6)->nullable()
                ->comment('Valor del subsidio por unidad cuando aplica');

            // Control de versiones
            $table->string('version', 5)
                ->comment('Versión del formato XML que aplica al detalle');
            $table->json('detallesAdicionales')->nullable()
                ->comment('Información adicional del detalle en formato JSON');

            // Campos de auditoría
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index(['nota_credito_id', 'linea']);
            $table->index('codigoPrincipal');
            $table->index(['nota_credito_id', 'codigoPrincipal']);
            $table->index('impuesto_codigo');
            $table->index('impuesto_codigoPorcentaje');

            // Índice para relación con factura detalle
            $table->index('factura_detalle_id');
            $table->comment('Almacena los detalles de las notas de crédito con su información de productos, servicios e impuestos');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nota_credito_detalles');
    }
};
