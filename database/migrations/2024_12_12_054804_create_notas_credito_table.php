<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();

            // Referencias internas del sistema
            // Si la nota de crédito es manual, estos campos pueden ser NULL
            // Si la nota de crédito se genera desde una factura del sistema, estos campos son obligatorios
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')
                ->comment('ID de la empresa emisora. Null si es nota de crédito manual');
            $table->foreignId('establecimiento_id')->nullable()->constrained('establecimientos')
                ->comment('ID del establecimiento emisor. Null si es nota de crédito manual');
            $table->foreignId('punto_emision_id')->nullable()->constrained('puntos_emision')
                ->comment('ID del punto de emisión. Null si es nota de crédito manual');

            // Control interno
            $table->uuid('uuid')->unique()
                ->comment('Identificador único universal para control interno');
            $table->string('estado', 20)->default('CREADA')
                ->comment('Estados posibles: CREADA, FIRMADA, ENVIADA, AUTORIZADA, RECHAZADA, ANULADA');
            $table->string('version', 5)
                ->comment('Versión del formato XML: 1.0.0, 1.1.0, 2.0.0, 2.1.0');

            // Relación con factura del sistema
            // Este campo será NULL cuando la nota de crédito sea manual
            $table->foreignId('factura_id')->nullable()->constrained('facturas')
                ->comment('ID de la factura relacionada del sistema. Null si es nota de crédito manual');

            // infoTributario - Campos requeridos por el SRI
            $table->string('ambiente', 1)
                ->comment('1: Pruebas, 2: Producción');
            $table->string('tipoEmision', 1)->default('1')
                ->comment('1: Emisión normal, 2: Emisión por contingencia');
            $table->string('razonSocial', 300)
                ->comment('Razón social del emisor');
            $table->string('nombreComercial', 300)->nullable()
                ->comment('Nombre comercial del emisor (opcional)');
            $table->string('ruc', 13)
                ->comment('RUC del emisor');
            $table->string('claveAcceso', 49)->unique()
                ->comment('Clave de acceso única generada según especificaciones del SRI');
            $table->string('codDoc', 2)->default('04')
                ->comment('04: Código de documento para nota de crédito');
            $table->string('estab', 3)
                ->comment('Código del establecimiento (3 dígitos)');
            $table->string('ptoEmi', 3)
                ->comment('Código del punto de emisión (3 dígitos)');
            $table->string('secuencial', 9)
                ->comment('Secuencial del documento (9 dígitos)');
            $table->string('dirMatriz', 300)
                ->comment('Dirección de la matriz del emisor');

            // infoNotaCredito - Información específica de la nota de crédito
            $table->date('fechaEmision')
                ->comment('Fecha de emisión de la nota de crédito');
            $table->string('dirEstablecimiento', 300)->nullable()
                ->comment('Dirección del establecimiento emisor (opcional)');
            $table->string('tipoIdentificacionComprador', 2)
                ->comment('04: RUC, 05: Cédula, 06: Pasaporte, 07: Consumidor Final, 08: Identificación del Exterior');
            $table->string('razonSocialComprador', 300)
                ->comment('Razón social del comprador');
            $table->string('identificacionComprador', 20)
                ->comment('Número de identificación del comprador');
            $table->string('contribuyenteEspecial', 13)->nullable()
                ->comment('Número de contribuyente especial del emisor');
            $table->string('obligadoContabilidad', 2)->nullable()
                ->comment('SI: Obligado a llevar contabilidad, NO: No obligado');
            $table->string('rise')->nullable()
                ->comment('Código RISE del contribuyente');

            // Información del documento modificado
            $table->string('codDocModificado', 2)
                ->comment('01: Factura que se modifica');
            $table->string('numDocModificado', 15)
                ->comment('Número completo del documento que se modifica (estab-ptoEmi-secuencial)');
            $table->date('fechaEmisionDocSustento')
                ->comment('Fecha de emisión del documento que se modifica');

            // Totales y valores
            $table->decimal('totalSinImpuestos', 14, 2)
                ->comment('Total antes de impuestos');
            $table->decimal('valorModificacion', 14, 2)
                ->comment('Valor total de la modificación incluyendo impuestos');
            $table->string('moneda', 15)->default('DOLAR')
                ->comment('Tipo de moneda, por defecto DOLAR');
            $table->text('motivo')
                ->comment('Razón o motivo de la emisión de la nota de crédito');
            $table->decimal('totalDescuento', 14, 2)
                ->comment('Suma total de los descuentos');
            $table->decimal('totalImpuestos', 14, 2)
                ->comment('Suma total de los impuestos');
            $table->decimal('valorTotal', 14, 2)
                ->comment('Valor total del documento');

            // Campos de control SRI
            $table->boolean('procesadoSri')->default(false)
                ->comment('Indica si el documento ya fue procesado por el SRI');
            $table->datetime('fechaAutorizacion')->nullable()
                ->comment('Fecha y hora de autorización por el SRI');
            $table->string('numeroAutorizacion', 49)->nullable()
                ->comment('Número de autorización otorgado por el SRI');
            $table->json('infoAdicional')->nullable()
                ->comment('Información adicional de la nota de crédito en formato JSON');

            // Control de versiones y auditoría
            $table->integer('version_actual')->default(1)
                ->comment('Versión actual del documento para control de cambios');
            $table->json('historial_cambios')->nullable()
                ->comment('Historial de cambios en formato JSON');
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización de consultas
            $table->index('estado');
            $table->index('fechaEmision');
            $table->index(['ruc', 'estab', 'ptoEmi', 'secuencial']);
            $table->index('identificacionComprador');
            $table->index('numDocModificado');
            $table->index(['empresa_id', 'establecimiento_id', 'punto_emision_id'], 'idx_nc_rel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};
