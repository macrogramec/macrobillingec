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
 *     name="Autenticaci�n",
 *     description="Endpoints para autenticaci�n y manejo de tokens"
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
            return $this->unauthorizedResponse('Credenciales inv�lidas');
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
            return $this->unauthorizedResponse('Cliente inv�lido');
        }

        // Validar ambientes si se proporcionaron
        if (!empty($requestData['environments'])) {
            if (!$this->authService->validateEnvironments($client, $requestData['environments'])) {
                return $this->forbiddenResponse('Ambientes no v�lidos para este cliente');
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
