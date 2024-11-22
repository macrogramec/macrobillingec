<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id();
            // Relación con la factura principal
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->integer('linea'); // Número de línea del detalle para orden

            // Campos de identificación del producto
            // <codigoPrincipal> - Obligatorio v1.0.0, v1.1.0, v2.0.0, v2.1.0
            $table->string('codigoPrincipal', 25)->nullable(); 
            
            // <codigoAuxiliar> - Opcional todas las versiones
            $table->string('codigoAuxiliar', 25)->nullable();
            
            // Campos descriptivos del producto
            // <descripcion> - Obligatorio todas las versiones - Razón por la que se emite la factura
            $table->string('descripcion', 300); 
            
            // <cantidad> - Obligatorio todas las versiones
            $table->decimal('cantidad', 14, 6);
            
            // Campos de precios unitarios
            // <precioUnitario> - Obligatorio todas las versiones - Precio unitario sin impuestos
            $table->decimal('precioUnitario', 14, 6);
            
            // <precioSinSubsidio> - Opcional v2.1.0 - Precio sin subsidio
            $table->decimal('precioSinSubsidio', 14, 6)->nullable();
            
            // Campos de descuentos
            // <descuento> - Obligatorio todas las versiones
            $table->decimal('descuento', 14, 2);
            
            // Campos de precios totales
            // <precioTotalSinImpuesto> - Obligatorio todas las versiones
            $table->decimal('precioTotalSinImpuesto', 14, 2);
            
            // Campos para detalles adicionales (v1.0.0 hasta v2.1.0)
            // <detallesAdicionales> - Opcional todas las versiones
            $table->json('detallesAdicionales')->nullable();

            // Campos para impuestos
            // <codigoImpuesto> - Obligatorio dentro del nodo impuesto
            $table->string('impuesto_codigo', 1)->default('2'); // 2: IVA
            
            // <codigoPorcentaje> - Obligatorio dentro del nodo impuesto
            $table->string('impuesto_codigoPorcentaje', 4); // 0: 0%, 2: 12%, 3: 14%, etc
            
            // <tarifa> - Obligatorio dentro del nodo impuesto
            $table->decimal('impuesto_tarifa', 14, 2);
            
            // <baseImponible> - Obligatorio dentro del nodo impuesto
            $table->decimal('impuesto_baseImponible', 14, 2);
            
            // <valor> - Obligatorio dentro del nodo impuesto
            $table->decimal('impuesto_valor', 14, 2);

            // Campos para ICE (si aplica)
            // Nodo opcional para ICE en todas las versiones
            $table->string('ice_codigo', 4)->nullable();
            $table->decimal('ice_tarifa', 14, 2)->nullable();
            $table->decimal('ice_baseImponible', 14, 2)->nullable();
            $table->decimal('ice_valor', 14, 2)->nullable();

            // Campos para IRBPNR (si aplica)
            // Nodo opcional para IRBPNR en todas las versiones
            $table->string('irbpnr_codigo', 4)->nullable();
            $table->decimal('irbpnr_tarifa', 14, 2)->nullable();
            $table->decimal('irbpnr_baseImponible', 14, 2)->nullable();
            $table->decimal('irbpnr_valor', 14, 2)->nullable();

            // Campos adicionales v2.1.0
            // <unidadMedida> - Opcional v2.1.0
            $table->string('unidadMedida', 50)->nullable();
            
            // <precioUnitarioSubsidio> - Opcional v2.1.0 - Valor del subsidio por unidad
            $table->decimal('precioUnitarioSubsidio', 14, 6)->nullable();
            
            // Campos para comercio exterior v2.1.0
            $table->string('codigoPartidaArancelaria', 15)->nullable();
            $table->decimal('precioReferencialUnitario', 14, 6)->nullable();
            $table->decimal('valoracionTotal', 14, 2)->nullable();
            $table->string('paisOrigen', 3)->nullable();
            $table->string('paisAdquisicion', 3)->nullable();
            $table->decimal('valorParteReforma', 14, 2)->nullable();
            
            // Control de versiones y timestamps
            $table->string('version', 5); // Versión del esquema que aplica a este detalle
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['factura_id', 'linea']);
            $table->index('codigoPrincipal');
            $table->index(['factura_id', 'codigoPrincipal']);
            $table->index('impuesto_codigo');
            $table->index('impuesto_codigoPorcentaje');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};