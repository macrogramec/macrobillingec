<?php

use App\Http\Controllers\Api\DocumentosControllers;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\AwsS3PdfXmlController;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\GuiaRemisionController;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\LiquidacionCompraController;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\RetencionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\FacturacionController;
use App\Http\Controllers\EndPoints\DocumentosElectronicos\NotaCreditoController;
use App\Http\Middleware\CheckScopes;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EndPoints\Empresa\EmpresaController;
use App\Http\Controllers\EndPoints\Empresa\EstablecimientoController;
use App\Http\Controllers\EndPoints\Empresa\PuntoEmisionController;
Route::post('reprocesar_documentos', [AwsS3PdfXmlController::class, 'reprocesarDocumento'])->name('reprocesar_documentos');

Route::middleware(['cors', 'api.logging'])->group(function () {
    // Rutas públicas
    Route::post('/create-first-admin', [AdminController::class, 'createFirstAdmin']);
    Route::post('/oauth/token', [AuthController::class, 'issueToken'])
        ->name('passport.token');
    Route::get('get-ride/{uuid}', [DocumentosControllers::class, 'obtenerRIDE']);
    // Rutas admin
    Route::middleware(['auth:api', 'scope:admin', 'client.limit'])->group(function () {
        Route::post('/create-user', [AdminController::class, 'createUser']);
        Route::get('/', [EmpresaController::class, 'index'])->name('empresas.index');

    });

    // Rutas user
    Route::middleware(['auth:api', 'scope:admin|user|desarrollo|produccion', 'client.limit'])->group(function () {
        Route::get('/user/info', [AdminController::class, 'getInfoUser']);
    });

    // Rutas empresas
    Route::middleware(['auth:api', 'scope:admin|user|desarrollo|produccion', 'client.limit'])->group(function () {
        Route::get('/sri/consultar-ruc', [App\Http\Controllers\Api\SRIController::class, 'consultarRuc']);


        Route::prefix('empresas')->group(function () {
            // CRUD Empresas
            Route::post('/', [EmpresaController::class, 'store'])->name('empresas.store');
            Route::get('/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
            Route::put('/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
            Route::delete('/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');

            // Firma electrónica
            Route::post('/actualizarFirma', [EmpresaController::class, 'actualizarFirmaElectronica'])
                ->name('actualizarFirma');
            Route::post('/actualizarFirmaArchivo', [EmpresaController::class, 'actualizarFirmaArchivo'])
                ->name('actualizarFirmaArchivo');
            Route::post('/actualizarLogo', [EmpresaController::class, 'actualizarLogo'])
                ->name('empresas.actualizarLogo');

            Route::put('/{empresa}/firma', [EmpresaController::class, 'updateClaveFirma'])
                ->name('empresas.update-clave-firma');

            // CRUD Establecimientos
            Route::prefix('{empresa}/establecimientos')->group(function () {
                Route::get('/', [EstablecimientoController::class, 'index'])
                    ->name('empresas.establecimientos.index');
                Route::post('/', [EstablecimientoController::class, 'store'])
                    ->name('empresas.establecimientos.store');
                Route::get('/{establecimiento}', [EstablecimientoController::class, 'show'])
                    ->name('empresas.establecimientos.show');
                Route::put('/{establecimiento}', [EstablecimientoController::class, 'update'])
                    ->name('empresas.establecimientos.update');
                Route::delete('/{establecimiento}', [EstablecimientoController::class, 'destroy'])
                    ->name('empresas.establecimientos.destroy');

                // CRUD Puntos de Emisión
                Route::prefix('{establecimiento}/puntos-emision')->group(function () {
                    Route::get('/', [PuntoEmisionController::class, 'index'])
                        ->name('empresas.establecimientos.puntos-emision.index');
                    Route::post('/', [PuntoEmisionController::class, 'store'])
                        ->name('empresas.establecimientos.puntos-emision.store');
                    Route::get('/{punto_emision}', [PuntoEmisionController::class, 'show'])
                        ->name('empresas.establecimientos.puntos-emision.show');
                    Route::put('/{punto_emision}', [PuntoEmisionController::class, 'update'])
                        ->name('empresas.establecimientos.puntos-emision.update');
                    Route::delete('/{punto_emision}', [PuntoEmisionController::class, 'destroy'])
                        ->name('empresas.establecimientos.puntos-emision.destroy');
                    Route::put('/{punto_emision}/secuencial', [PuntoEmisionController::class, 'updateSecuencial'])
                        ->name('empresas.establecimientos.puntos-emision.update-secuencial');
                })->scopeBindings();
            })->scopeBindings();
        });
        Route::prefix('facturacion')->group(function () {
            // Crear nueva factura
            Route::post('/', [FacturacionController::class, 'store'])
                ->name('facturacion.store');

            // Consultar factura
            Route::get('/{claveAcceso}', [FacturacionController::class, 'show'])
                ->name('facturacion.show')
                ->where('claveAcceso', '[0-9]{49}');

            // Anular factura
            Route::post('/{claveAcceso}/anular', [FacturacionController::class, 'anular'])
                ->name('facturacion.anular')
                ->where('claveAcceso', '[0-9]{49}');

            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [FacturacionController::class, 'descargarPDF'])
                ->name('facturacion.pdf-download')
                ->where('claveAcceso', '[0-9]{49}');

            Route::get('/{claveAcceso}/xml', [FacturacionController::class, 'descargarXML'])
                ->name('facturacion.xml')
                ->where('claveAcceso', '[0-9]{49}');

            // Consultar estado en SRI
            Route::get('/{claveAcceso}/estado-sri', [FacturacionController::class, 'consultarEstadoSRI'])
                ->name('facturacion.estado-sri')
                ->where('claveAcceso', '[0-9]{49}');
        });
        Route::prefix('notas-credito')->group(function () {
            // Crear nota de crédito externa (no emitida en el sistema)
            Route::post('/externa', [NotaCreditoController::class, 'crearExterna'])
                ->name('notas-credito.externa.store');

            // Crear nota de crédito interna (emitida en el sistema)
            Route::post('/interna', [NotaCreditoController::class, 'crearInterna'])
                ->name('notas-credito.interna.store');

            // Consultar nota de crédito
            Route::get('/{claveAcceso}', [NotaCreditoController::class, 'show'])
                ->name('notas-credito.show')
                ->where('claveAcceso', '[0-9]{49}');
// Anular nota de credito
            Route::post('/{claveAcceso}/anular', [NotaCreditoController::class, 'anular'])
                ->name('notas-credito.anular')
                ->where('claveAcceso', '[0-9]{49}');
            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [NotaCreditoController::class, 'descargarPDF'])
                ->name('notas-credito.pdf')
                ->where('claveAcceso', '[0-9]{49}');

            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [NotaCreditoController::class, 'descargarPDF'])
                ->name('notas-credito.pdf-download')
                ->where('claveAcceso', '[0-9]{49}');

            Route::get('/{claveAcceso}/xml', [NotaCreditoController::class, 'descargarXML'])
                ->name('notas-credito.xml')
                ->where('claveAcceso', '[0-9]{49}');
        });
        Route::prefix('retenciones')->group(function () {
            // Primero define las rutas específicas
            Route::get('/codigos-retencion', [RetencionController::class, 'codigosRetenciones'])
                ->name('retenciones.codigosRetenciones');

            // Después define las rutas con parámetros
            Route::get('/{id_empresa}', [RetencionController::class, 'index'])
                ->name('retenciones.index')
                ->where('id_empresa', '[0-9]+'); // Asegura que id_empresa sea numérico

            // Crear retención
            Route::post('/crear', [RetencionController::class, 'store'])
                ->name('retenciones.store');

            // Consultar retención por ID
            Route::get('/{uuid}', [RetencionController::class, 'show'])
                ->name('retenciones.show')
                ->where('id', '[0-9]+');

            // Consultar por clave de acceso
            Route::get('/clave-acceso/{claveAcceso}', [RetencionController::class, 'consultarPorClaveAcceso'])
                ->name('retenciones.consultar-clave')
                ->where('claveAcceso', '[0-9]{49}');

            // Anular retención
            Route::post('/{uuid}/anular', [RetencionController::class, 'anular'])
                ->name('retenciones.anular')
                ->where('id', '[0-9]+');

            // Estado SRI
            Route::get('/{claveAcceso}/estado-sri', [RetencionController::class, 'consultarEstadoSRI'])
                ->name('retenciones.estado-sri')
                ->where('claveAcceso', '[0-9]{49}');
            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [RetencionController::class, 'descargarPDF'])
                ->name('retenciones.pdf-download')
                ->where('claveAcceso', '[0-9]{49}');
            // Retención XML
            Route::get('/{claveAcceso}/xml', [RetencionController::class, 'descargarXML'])
                ->name('retenciones.xml')
                ->where('claveAcceso', '[0-9]{49}');

        });
        Route::prefix('liquidaciones-compra')->group(function () {
            // Crear liquidación de compra
            Route::post('/', [LiquidacionCompraController::class, 'store'])
                ->name('liquidaciones-compra.store');

            // Consultar liquidación de compra
            Route::get('/{claveAcceso}', [LiquidacionCompraController::class, 'show'])
                ->name('liquidaciones-compra.show')
                ->where('claveAcceso', '[0-9]{49}');

            // Autorizar liquidación de compra
            Route::post('/{claveAcceso}/autorizar', [LiquidacionCompraController::class, 'autorizar'])
                ->name('liquidaciones-compra.autorizar')
                ->where('claveAcceso', '[0-9]{49}');

            // Anular liquidación de compra
            Route::post('/{claveAcceso}/anular', [LiquidacionCompraController::class, 'anular'])
                ->name('liquidaciones-compra.anular')
                ->where('claveAcceso', '[0-9]{49}');

            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [LiquidacionCompraController::class, 'descargarPDF'])
                ->name('liquidaciones-compra.pdf')
                ->where('claveAcceso', '[0-9]{49}');
            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [LiquidacionCompraController::class, 'descargarPDF'])
                ->name('liquidaciones-compra.pdf-download')
                ->where('claveAcceso', '[0-9]{49}');

            Route::get('/{claveAcceso}/xml', [LiquidacionCompraController::class, 'descargarXML'])
                ->name('liquidaciones-compra.xml')
                ->where('claveAcceso', '[0-9]{49}');
        });



        Route::prefix('guias-remision')->group(function () {
            Route::post('/', [GuiaRemisionController::class, 'store'])
                ->name('guias-remision.store');

            Route::get('/{claveAcceso}', [GuiaRemisionController::class, 'show'])
                ->name('guias-remision.show')
                ->where('claveAcceso', '[0-9]{49}');

            Route::post('/{claveAcceso}/anular', [GuiaRemisionController::class, 'anular'])
                ->name('guias-remision.anular')
                ->where('claveAcceso', '[0-9]{49}');
            // Descargar documentos
            Route::get('/{claveAcceso}/pdf', [GuiaRemisionController::class, 'descargarPDF'])
                ->name('guias-remision.pdf-download')
                ->where('claveAcceso', '[0-9]{49}');
            // Guía de Remisión XML
            Route::get('/{claveAcceso}/xml', [GuiaRemisionController::class, 'descargarXML'])
                ->name('guias-remision.xml')
                ->where('claveAcceso', '[0-9]{49}');
        });
    });


});

// Manejo OPTIONS
Route::options('/{any}', function () {
    return response()->json(null, 200);
})->where('any', '.*');
