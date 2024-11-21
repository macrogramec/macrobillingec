<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }
    
    protected $policies = [];

    public function boot(): void
    {
        // Instalar rutas de Passport
        Passport::ignoreRoutes();

        // Definir scopes si los necesitas
        Passport::tokensCan([
            'admin' => 'Acceso de administrador',
            'user' => 'Acceso de usuario',
            'desarrollo' => 'Desarrollo web',
            'produccion' => 'Produccion web',
            // Añade más scopes según necesites
        ]);

        Passport::enablePasswordGrant();
        
        // Configurar tiempo de expiración de tokens
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}


