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
        Schema::create('liquidaciones_compra', function (Blueprint $table) {
            $table->id();

            // Control interno
            $table->uuid('uuid')->unique();
            $table->string('estado', 20)->default('CREADA');
            $table->string('version', 5);

            // Relaciones
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('establecimiento_id')->constrained('establecimientos');
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');

            // infoTributario (XML: infoTributaria)
            $table->string('ambiente', 1); // 1: Pruebas, 2: Producción
            $table->string('tipo_emision', 1)->default('1'); // 1: Normal, 2: Indisponibilidad
            $table->string('razon_social', 300);
            $table->string('nombre_comercial', 300)->nullable();
            $table->string('ruc', 13);
            $table->string('clave_acceso', 49)->unique();
            $table->string('cod_doc', 2)->default('03'); // 03: Liquidación de compra
            $table->string('estab', 3);
            $table->string('pto_emi', 3);
            $table->string('secuencial', 9);
            $table->string('dir_matriz', 300);

            // Datos del documento (XML: infoLiquidacionCompra)
            $table->date('fecha_emision');
            $table->string('periodo_fiscal', 7)->nullable(); // Formato: MM/YYYY
            $table->string('dir_establecimiento', 300)->nullable();
            $table->string('contribuyente_especial', 13)->nullable();
            $table->string('obligado_contabilidad', 2)->nullable(); // SI/NO
            $table->string('comercio_exterior')->nullable(); // SI/NO
            $table->string('inco_term')->nullable(); // Para comercio exterior
            $table->string('lugar_inco_term')->nullable();
            $table->string('pais_origen', 3)->nullable(); // Código ISO país
            $table->string('puerto_embarque', 300)->nullable();
            $table->string('puerto_destino', 300)->nullable();
            $table->string('pais_destino', 3)->nullable(); // Código ISO país
            $table->string('pais_adquisicion', 3)->nullable(); // Código ISO país

            // Datos del proveedor
            $table->string('tipo_identificacion_proveedor', 2); // 04:RUC, 05:Cédula, 06:Pasaporte, etc
            $table->string('identificacion_proveedor', 20);
            $table->string('razon_social_proveedor', 300);
            $table->string('direccion_proveedor', 300);
            $table->string('tipo_proveedor', 30)->nullable(); // sociedad, persona_natural
            $table->string('regimen_proveedor', 30)->nullable(); // rimpe, general
            $table->string('email_proveedor', 300)->nullable();
            $table->string('telefono_proveedor', 30)->nullable();

            // Totales
            $table->decimal('total_sin_impuestos', 14, 2);
            $table->decimal('total_descuento', 14, 2);
            $table->decimal('total_ice', 14, 2)->default(0);
            $table->decimal('total_iva', 14, 2)->default(0);
            $table->decimal('total_irbpnr', 14, 2)->default(0);
            $table->decimal('total_sin_impuestos_sin_ice', 14, 2)->default(0);
            $table->decimal('total_subsidio', 14, 2)->default(0);
            $table->decimal('total_descuento_adicional', 14, 2)->default(0);
            $table->decimal('total_exonerado', 14, 2)->default(0);
            $table->decimal('total_impuestos', 14, 2);
            $table->decimal('importe_total', 14, 2);
            $table->string('moneda', 15)->default('DOLAR');

            // Reembolsos
            $table->string('cod_doc_reembolso', 2)->nullable();
            $table->decimal('total_comprobantes_reembolso', 14, 2)->nullable();
            $table->decimal('total_base_imponible_reembolso', 14, 2)->nullable();
            $table->decimal('total_impuesto_reembolso', 14, 2)->nullable();

            // Pagos
            $table->decimal('pagos_total', 14, 2)->nullable();
            $table->integer('pagos_plazo')->nullable();
            $table->string('pagos_unidad_tiempo', 10)->nullable(); // dias, meses

            // Máquina fiscal
            $table->json('maquina_fiscal')->nullable(); // marca, modelo, serie

            // Control SRI
            $table->boolean('procesado_sri')->default(false);
            $table->datetime('fecha_autorizacion')->nullable();
            $table->string('numero_autorizacion', 49)->nullable();
            $table->string('ambiente_autorizacion', 1)->nullable();
            $table->json('motivos_rechazo')->nullable();

            // Campos adicionales
            $table->json('info_adicional')->nullable();
            $table->integer('version_actual')->default(1);
            $table->json('historial_cambios')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('estado');
            $table->index('fecha_emision');
            $table->index(['ruc', 'estab', 'pto_emi', 'secuencial'], 'lc_comprobante_index');
            $table->index('identificacion_proveedor');
            $table->index('version');
            $table->index('clave_acceso');
            $table->index('secuencial');
            $table->index('procesado_sri');
        });



        // Tabla para reembolsos
        Schema::create('liquidacion_compra_reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_compra_id')->constrained('liquidaciones_compra')->cascadeOnDelete();

            $table->string('tipo_identificacion_proveedor', 2);
            $table->string('identificacion_proveedor', 20);
            $table->string('cod_pais_pago_proveedor', 3);
            $table->string('tipo_proveedor', 2);
            $table->json('doc_reembolso'); // Almacena código, estab, punto_emision, secuencial, fecha_emision, autorizacion
            $table->decimal('total_comprobante', 14, 2);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['liquidacion_compra_id', 'identificacion_proveedor'], 'lc_reembolsos_id_prov_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_compra_reembolsos');
        Schema::dropIfExists('liquidaciones_compra');
    }
};
