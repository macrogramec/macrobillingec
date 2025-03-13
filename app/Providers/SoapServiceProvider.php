<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SoapSriService;

class SoapServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SoapSriService::class, function ($app) {
            return new SoapSriService(
                config('services.sri.wsdl_url', 'http://172.30.2.28:8050/SERVI_GENERAR_XML.svc?singleWsdl'),
                config('services.sri.service_url', 'http://172.30.2.28:8050/SERVI_GENERAR_XML.svc')
            );
        });
    }
}
