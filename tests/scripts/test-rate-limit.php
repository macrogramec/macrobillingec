<?php

// Configuración
$baseUrl = 'http://54.185.122.131'; // Ajusta según tu URL
$token = ''; // Aquí pondremos el token que obtengamos

// Función para obtener token
function getToken($baseUrl) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $baseUrl . '/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'password',
            'client_id' => '3', // Tu client_id
            'client_secret' => 'vESMJmm8RqxC7Mm16YGk3WyIx455qR7GkmFzocD0', // Tu client_secret
            'username' => 'admin@macrobillingec.com', // Tu email de admin
            'password' => 'macrobilling*123', // Tu contraseña
            'scope' => 'admin user'
        ])
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Función para hacer peticiones de prueba
function makeRequest($baseUrl, $token) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $baseUrl . '/api/profile',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $headers = curl_getinfo($curl, CURLINFO_HEADER_OUT);
    curl_close($curl);

    return [
        'code' => $httpCode,
        'response' => $response,
        'headers' => $headers
    ];
}

// Obtener token
$tokenResponse = getToken($baseUrl);
if (isset($tokenResponse['access_token'])) {
    $token = $tokenResponse['access_token'];
    echo "Token obtenido correctamente\n";
} else {
    die("Error obteniendo token: " . print_r($tokenResponse, true));
}

// Hacer múltiples peticiones rápidamente
$requestCount = 0;
$maxRequests = 600; // Número de peticiones a realizar
$startTime = microtime(true);

while ($requestCount < $maxRequests) {
    $result = makeRequest($baseUrl, $token);
    $requestCount++;
    
    // Verificar headers de rate limit
    echo "Petición #$requestCount - HTTP Code: {$result['code']}\n";
    
    if ($result['code'] === 429) {
        echo "¡Rate limit alcanzado después de $requestCount peticiones!\n";
        break;
    }

    // Pequeña pausa para no sobrecargar
    usleep(10000); // 10ms de pausa
}

$endTime = microtime(true);
$duration = $endTime - $startTime;

echo "\nResumen de la prueba:\n";
echo "Total peticiones: $requestCount\n";
echo "Tiempo total: " . round($duration, 2) . " segundos\n";
echo "Peticiones por segundo: " . round($requestCount / $duration, 2) . "\n";