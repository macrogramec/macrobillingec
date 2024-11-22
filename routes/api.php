<?php
// routes/api.php
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CheckScopes;
use App\Http\Controllers\AdminController;


Route::middleware(['cors', 'api.logging'])->group(function () {
    // Rutas sin autenticaci—n (no aplicamos rate limiting aqu’ ya que son rutas pœblicas)
    Route::post('/create-first-admin', [AdminController::class, 'createFirstAdmin']);
    Route::post('/oauth/token', [AuthController::class, 'issueToken'])
        ->name('passport.token');

    // Rutas para administradores
    Route::middleware(['auth:api', 'scope:admin', 'client.limit'])->group(function () {
        Route::post('/create-user', [AdminController::class, 'createUser']);
    });

    // Rutas para admins y usuarios regulares
    Route::middleware(['auth:api', 'scope:admin,user', 'client.limit'])->group(function () {
        Route::get('/profile', function () {
            return auth()->user();
        });
    });
});

// Manejamos las peticiones OPTIONS
Route::options('/{any}', function () {
    return response()->json(null, 200);
})->where('any', '.*');