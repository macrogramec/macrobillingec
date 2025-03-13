<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Primero agregamos los campos faltantes
        Schema::table('facturas', function (Blueprint $table) {
            if (!Schema::hasColumn('facturas', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id')->nullable()->after('id')
                    ->comment('ID de la empresa emisora de la factura');
                $table->foreign('empresa_id')->references('id')->on('empresas');
            }

            if (!Schema::hasColumn('facturas', 'establecimiento_id')) {
                $table->unsignedBigInteger('establecimiento_id')->nullable()->after('empresa_id')
                    ->comment('ID del establecimiento emisor de la factura');
                $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            }

            if (!Schema::hasColumn('facturas', 'punto_emision_id')) {
                $table->unsignedBigInteger('punto_emision_id')->nullable()->after('establecimiento_id')
                    ->comment('ID del punto de emisión de la factura');
                $table->foreign('punto_emision_id')->references('id')->on('puntos_emision');
            }

            // Añadir índice compuesto para las relaciones
            $table->index(['empresa_id', 'establecimiento_id', 'punto_emision_id'], 'idx_facturas_relaciones');

            // Documentación de la tabla
            $table->comment('Almacena las facturas electrónicas del sistema con información tributaria y de control');

            // Documentación de las columnas principales
            $table->string('uuid')->comment('Identificador único universal para control interno')->change();
            $table->string('estado', 20)->default('CREADA')->comment('Estado actual del documento: CREADA, FIRMADA, ENVIADA, AUTORIZADA, RECHAZADA')->change();
            $table->string('version', 5)->comment('Versión del formato XML: 1.0.0, 1.1.0, 2.0.0, 2.1.0')->change();

            // Documentación campos infoTributario
            $table->string('ambiente', 1)->comment('1: Pruebas, 2: Producción')->change();
            $table->string('tipoEmision', 1)->default('1')->comment('1: Normal, 2: Contingencia')->change();
            $table->string('razonSocial', 300)->comment('Razón social del emisor del documento')->change();
            $table->string('nombreComercial', 300)->nullable()->comment('Nombre comercial del emisor')->change();
            $table->string('ruc', 13)->comment('RUC del emisor del documento')->change();
            $table->string('claveAcceso', 49)->unique()->comment('Clave de acceso única generada según especificaciones del SRI')->change();
            $table->string('codDoc', 2)->default('01')->comment('Código del tipo de documento (01: Factura)')->change();
            $table->string('estab', 3)->comment('Código del establecimiento (3 dígitos)')->change();
            $table->string('ptoEmi', 3)->comment('Código del punto de emisión (3 dígitos)')->change();
            $table->string('secuencial', 9)->comment('Secuencial del documento (9 dígitos)')->change();
            $table->string('dirMatriz', 300)->comment('Dirección de la matriz del emisor')->change();

            // Documentación campos infoFactura
            $table->date('fechaEmision')->comment('Fecha de emisión de la factura')->change();
            $table->string('dirEstablecimiento', 300)->nullable()->comment('Dirección del establecimiento emisor')->change();
            $table->string('contribuyenteEspecial', 13)->nullable()->comment('Número de contribuyente especial del emisor')->change();
            $table->string('obligadoContabilidad', 2)->nullable()->comment('SI: Obligado a llevar contabilidad, NO: No obligado')->change();
            $table->string('comercioExterior', 2)->nullable()->comment('Indicador de comercio exterior (EXPORTADOR)')->change();

            // Documentación campos del comprador
            $table->string('tipoIdentificacionComprador', 2)->comment('04:RUC, 05:Cédula, 06:Pasaporte, 07:Consumidor Final')->change();
            $table->string('razonSocialComprador', 300)->comment('Razón social del comprador')->change();
            $table->string('identificacionComprador', 20)->comment('Número de identificación del comprador')->change();
            $table->string('direccionComprador', 300)->nullable()->comment('Dirección del comprador')->change();

            // Documentación campos de totales
            $table->decimal('totalSinImpuestos', 14, 2)->comment('Total de la factura antes de impuestos')->change();
            $table->decimal('totalDescuento', 14, 2)->comment('Total de descuentos aplicados')->change();
            $table->decimal('totalImpuestos', 14, 2)->comment('Total de impuestos')->change();
            $table->decimal('importeTotal', 14, 2)->comment('Importe total de la factura')->change();
            $table->string('moneda', 15)->default('DOLAR')->comment('Tipo de moneda, por defecto DOLAR')->change();

            // Documentación campos de control SRI
            $table->boolean('procesadoSri')->default(false)->comment('Indica si el documento fue procesado por el SRI')->change();
            $table->dateTime('fechaAutorizacion')->nullable()->comment('Fecha y hora de autorización por el SRI')->change();
            $table->string('numeroAutorizacion', 49)->nullable()->comment('Número de autorización otorgado por el SRI')->change();
            $table->json('infoAdicional')->nullable()->comment('Información adicional en formato JSON')->change();
            $table->json('motivos')->nullable()->comment('Motivos de rechazo o advertencias del SRI en formato JSON')->change();

            // Documentación campos de auditoría
            // Documentación campos de auditoría
            $table->timestamp('created_at')->nullable()->comment('Fecha y hora de creación del registro')->change();
            $table->timestamp('updated_at')->nullable()->comment('Fecha y hora de última actualización')->change();
            $table->timestamp('deleted_at')->nullable()->comment('Fecha y hora de eliminación lógica')->change();
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex('idx_facturas_relaciones');
            $table->dropForeign(['punto_emision_id']);
            $table->dropForeign(['establecimiento_id']);
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id', 'establecimiento_id', 'punto_emision_id']);
        });
    }
};
