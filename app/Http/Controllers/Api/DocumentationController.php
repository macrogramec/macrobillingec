<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Facturación Electrónica Ecuador",
 *     description="Sistema de facturación electrónica para el SRI Ecuador",
 *     @OA\Contact(
 *         email="soporte@macrobilling.com",
 *         name="Soporte Macrogram"
 *     ),
 *     @OA\License(
 *         name="Privada",
 *         url="https://www.macrobilling.com"
 *     )
 * )
 *
 * @OA\Server(
 *     description="API Server",
 *     url=L5_SWAGGER_CONST_HOST
 * )
 *
 * @OA\SecurityScheme(
 *     type="oauth2",
 *     description="Autenticación OAuth2",
 *     name="OAuth2",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="oauth2",
 *     in="header",
 *     @OA\Flow(
 *         flow="password",
 *         tokenUrl="/oauth/token",
 *         refreshUrl="/oauth/token/refresh",
 *         scopes={
 *             "admin": "Acceso de administrador",
 *             "user": "Acceso de usuario",
 *             "desarrollo": "Acceso ambiente desarrollo",
 *             "produccion": "Acceso ambiente producción"
 *         }
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para autenticación y gestión de tokens"
 * )
 *
 * @OA\Tag(
 *     name="SRI",
 *     description="Endpoints relacionados con el Servicio de Rentas Internas"
 * )
 */
class DocumentationController extends Controller
{
    //
}
