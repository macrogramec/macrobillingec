<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function validateClient(string $clientId, string $clientSecret): ?Client
    {
        return Client::where('id', $clientId)
            ->where('secret', $clientSecret)
            ->first();
    }

    public function validateScopes(User $user, array $requestedScopes): bool
    {
        return $user->hasScopes($requestedScopes);
    }

    public function validateEnvironments(Client $client, array $requestedEnvironments): bool
    {
        $availableEnvironments = $client->environments ?? [];
        $validEnvironments = array_intersect($requestedEnvironments, $availableEnvironments);
        return count($requestedEnvironments) === count($validEnvironments);
    }

    public function processTokenRequest($request, $authServer, $tokens)
    {
        try {
            $tokenResponse = $authServer->respondToAccessTokenRequest($request, new \Nyholm\Psr7\Response());
            return json_decode((string) $tokenResponse->getBody(), true);
        } catch (\Exception $e) {
            throw new \Exception('Error al generar el token: ' . $e->getMessage());
        }
    }
}