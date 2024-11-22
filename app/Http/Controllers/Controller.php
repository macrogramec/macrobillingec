<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0",
 *         title="API de Facturación Electrónica",
 *         description="API REST para facturación electrónica a gran escala en Ecuador",
 *         @OA\Contact(
 *             email="soporte@macrobilling.com"
 *         )
 *     ),
 *     @OA\Server(
 *         description="Servidor de Desarrollo",
 *         url="http://54.185.122.131/api"
 *     ),
 *     @OA\Server(
 *         description="Servidor de Producción",
 *         url="https://api.macrobilling.com/api"
 *     )
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}