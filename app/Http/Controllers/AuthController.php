<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Services\AuthService;
use App\Http\Requests\AuthLoginRequest;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Endpoints para autenticación y manejo de tokens"
 * )
 */
class AuthController extends AccessTokenController
{
    use ApiResponse;

    protected $authService;

    public function __construct(
        AuthorizationServer $server,
        TokenRepository $tokens,
        AuthService $authService
    ) {
        parent::__construct($server, $tokens);
        $this->authService = $authService;
    }

/**
     * @OA\Post(
     *     path="/oauth/token",
     *     summary="Obtener token de acceso",
     *     description="Genera un token OAuth2 para autenticación",
     *     tags={"Autenticación"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type","client_id","client_secret","username","password"},
     *             @OA\Property(
     *                 property="grant_type",
     *                 type="string",
     *                 example="password",
     *                 description="Tipo de autenticación"
     *             ),
     *             @OA\Property(
     *                 property="client_id",
     *                 type="string",
     *                 example="2",
     *                 description="ID del cliente OAuth2"
     *             ),
     *             @OA\Property(
     *                 property="client_secret",
     *                 type="string",
     *                 description="Secreto del cliente OAuth2"
     *             ),
     *             @OA\Property(
     *                 property="username",
     *                 type="string",
     *                 format="email",
     *                 example="usuario@ejemplo.com",
     *                 description="Correo electrónico del usuario"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 example="password123",
     *                 description="Contraseña del usuario"
     *             ),
     *             @OA\Property(
     *                 property="scope",
     *                 type="string",
     *                 example="admin",
     *                 description="Permisos solicitados"
     *             ),
     *             @OA\Property(
     *                 property="environments",
     *                 type="array",
     *                 @OA\Items(type="string", enum={"desarrollo", "produccion"}),
     *                 description="Ambientes solicitados"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Credenciales inválidas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Scopes no autorizados",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No tienes permisos para estos scopes")
     *         )
     *     )
     * )
     */
    public function issueToken(ServerRequestInterface $request)
    {
        $request = app(AuthLoginRequest::class);
        $requestData = $request->validated();

        // Validar credenciales
        $user = $this->authService->validateCredentials(
            $requestData['username'],
            $requestData['password']
        );

        if (!$user) {
            return $this->unauthorizedResponse('Credenciales inv‡lidas');
        }

        // Validar scopes
        $requestedScopes = explode(' ', $requestData['scope']);
        if (!$this->authService->validateScopes($user, $requestedScopes)) {
            return $this->forbiddenResponse('No tienes permisos para estos scopes');
        }

        // Validar cliente
        $client = $this->authService->validateClient(
            $requestData['client_id'],
            $requestData['client_secret']
        );

        if (!$client) {
            return $this->unauthorizedResponse('Cliente inv‡lido');
        }

        // Validar ambientes si se proporcionaron
        if (!empty($requestData['environments'])) {
            if (!$this->authService->validateEnvironments($client, $requestData['environments'])) {
                return $this->forbiddenResponse('Ambientes no v‡lidos para este cliente');
            }
        }

        try {
            $tokenData = $this->authService->processTokenRequest(
                $request, 
                $this->server, 
                $this->tokens
            );

            return $this->successResponse(
                $tokenData,
                'Token generado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al generar el token',
                500
            );
        }
    }
}