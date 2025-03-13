<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            // Control interno del sistema
            $table->uuid('uuid')->unique();
            $table->string('estado', 20)->default('CREADA');

            // Control de versiones
            $table->string('version', 5); // 1.0.0, 1.1.0, 2.0.0, 2.1.0

            // infoTributario (Compatible con todas las versiones)
            $table->string('ambiente', 1); // 1: Pruebas, 2: Producci�n
            $table->string('tipoEmision', 1)->default('1');
            $table->string('razonSocial', 300);
            $table->string('nombreComercial', 300)->nullable();
            $table->string('ruc', 13);
            $table->string('claveAcceso', 49)->unique();
            $table->string('codDoc', 2)->default('01');
            $table->string('estab', 3);
            $table->string('ptoEmi', 3);
            $table->string('secuencial', 9);
            $table->string('dirMatriz', 300);

            // infoFactura (Campos comunes)
            $table->date('fechaEmision');
            $table->string('dirEstablecimiento', 300)->nullable();
            $table->string('contribuyenteEspecial', 13)->nullable();
            $table->string('obligadoContabilidad', 2)->nullable(); // SI, NO
            $table->string('comercioExterior', 2)->nullable(); // EXPORTADOR (v2.1.0)
            $table->string('incoTermFactura', 10)->nullable(); // v2.1.0
            $table->string('lugarIncoTerm', 300)->nullable(); // v2.1.0
            $table->string('paisOrigen', 3)->nullable(); // v2.1.0
            $table->string('puertoEmbarque', 300)->nullable(); // v2.1.0
            $table->string('puertoDestino', 300)->nullable(); // v2.1.0
            $table->string('paisDestino', 3)->nullable(); // v2.1.0
            $table->string('paisAdquisicion', 3)->nullable(); // v2.1.0

            // Datos del comprador
            $table->string('tipoIdentificacionComprador', 2);
            $table->string('guiaRemision', 20)->nullable();
            $table->string('razonSocialComprador', 300);
            $table->string('identificacionComprador', 20);
            $table->string('direccionComprador', 300)->nullable();
            $table->string('emailComprador', 300)->nullable();
            $table->string('placa', 20)->nullable(); // v2.1.0 para ventas a transportistas

            // Totales
            $table->decimal('totalSinImpuestos', 14, 2);
            $table->decimal('totalSubsidio', 14, 2)->default(0); // v2.1.0
            $table->decimal('incoTermTotal', 14, 2)->default(0); // v2.1.0
            $table->decimal('totalDescuento', 14, 2);
            $table->decimal('totalDescuentoAdicional', 14, 2)->default(0); // v2.1.0
            $table->decimal('totalSinImpuestosSinIce', 14, 2)->default(0); // v2.1.0
            $table->decimal('totalImpuestos', 14, 2);
            $table->decimal('totalExonerado', 14, 2)->default(0); // v2.1.0
            $table->decimal('totalIce', 14, 2)->default(0);
            $table->decimal('totalIva', 14, 2)->default(0);
            $table->decimal('totalIRBPNR', 14, 2)->default(0);
            $table->decimal('propina', 14, 2)->default(0);
            $table->decimal('fleteInternacional', 14, 2)->default(0); // v2.1.0
            $table->decimal('seguroInternacional', 14, 2)->default(0); // v2.1.0
            $table->decimal('gastosAduaneros', 14, 2)->default(0); // v2.1.0
            $table->decimal('gastosTransporteOtros', 14, 2)->default(0); // v2.1.0
            $table->decimal('importeTotal', 14, 2);
            $table->string('moneda', 15)->default('DOLAR');

            // Campos para retenciones
            $table->decimal('valorRetIva', 14, 2)->default(0);
            $table->decimal('valorRetRenta', 14, 2)->default(0);

            // Campos de pago
            $table->decimal('pagos_total', 14, 2)->default(0);
            $table->decimal('pagos_total_anticipos', 14, 2)->default(0); // v2.1.0
            $table->decimal('pagos_saldo_pendiente', 14, 2)->default(0); // v2.1.0

            // Campos adicionales v2.1.0
            $table->string('regimenFiscal', 3)->nullable();
            $table->string('agenteRetencion', 5)->nullable();
            $table->string('contratoProveedorEstado', 10)->nullable(); // N�mero de CPC
            $table->string('maquinaFiscal', 30)->nullable();
            $table->string('tipoProveedorRegimenMicroempresas', 2)->nullable(); // 01: Contribuyente r�gimen RIMPE

            // Campos de control SRI
            $table->string('digitoVerificador', 8)->nullable();
            $table->datetime('fechaAutorizacion')->nullable();
            $table->string('numeroAutorizacion', 49)->nullable();
            $table->string('ambienteAutorizacion', 1)->nullable();
            $table->json('infoAdicional')->nullable();
            $table->json('motivos')->nullable(); // Para almacenar m�ltiples motivos de rechazo
            $table->boolean('procesadoSri')->default(false);

            // Control de versiones del documento
            $table->integer('version_actual')->default(1);
            $table->json('historial_cambios')->nullable();

            // Control de timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // �ndices
            $table->index('estado');
            $table->index('fechaEmision');
            $table->index(['ruc', 'estab', 'ptoEmi', 'secuencial']);
            $table->index('identificacionComprador');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
