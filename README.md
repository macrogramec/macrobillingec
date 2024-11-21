# Sistema de Facturación Electrónica del Ecuador a Gran Escala

**Desarrollado por MACROGRAM CIA LTDA.**  
**Ubicación: Guayaquil - Daule - Ecuador**

---

## Descripción del Proyecto

Este sistema es una **API REST FULL** desarrollada en **Laravel 11** para la gestión de facturación electrónica en el Ecuador. Diseñado para manejar grandes volúmenes de información, el sistema soporta más de 1,000,000 de facturas, con validaciones en tiempo real y conexión directa con el Servicio de Rentas Internas (SRI).

### Características Principales
- **Escalabilidad**: Diseñado para soportar grandes volúmenes de datos y múltiples empresas.
- **Autenticación Segura**: Implementación de OAuth2 para el manejo de credenciales y accesos seguros.
- **Documentación Swagger**: Interfaz interactiva para explorar y probar los endpoints de la API.
- **Ambientes Separados**: Gestión de claves para desarrollo y producción.
- **Validación en Tiempo Real**: Verificación inmediata de los datos antes de su envío al SRI.

### Características de Seguridad Implementadas

1. **OAuth2 y Gestión de Tokens**
    - Sistema de autenticación robusto
    - Gestión de scopes (admin, user, desarrollo, produccion)
    - Control de acceso granular
    - Tokens con tiempo de expiración configurable

2. **Rate Limiting**
    - Control de peticiones por usuario y scope
    - Límites personalizados según el tipo de usuario:
        - Admin: 2000 peticiones/minuto
        - Desarrollo: 1000 peticiones/minuto
        - Producción: 1500 peticiones/minuto
        - Usuario base: 500 peticiones/minuto
    - Headers informativos de límites y uso

3. **CORS (Cross-Origin Resource Sharing)**
    - Configuración permisiva pero segura
    - Permite peticiones desde cualquier origen
    - Mantiene la seguridad mediante OAuth2
    - Headers configurados correctamente
    - Soporte para peticiones OPTIONS

4. **Sistema de Logging**
    - Registro detallado de todas las peticiones
    - Logs separados para errores y peticiones normales
    - Rotación diaria de logs
    - Información detallada sin datos sensibles
    - Monitoreo de performance

## Detalles de Implementación

### 1. Rate Limiting

#### Middleware de Rate Limiting
```php
// app/Http/Middleware/ClientRateLimiting.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ClientRateLimiting
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado',
                'code' => 'UNAUTHORIZED'
            ], 401);
        }

        $scopes = $user->scopes ?? [];
        $limit = $this->getLimitByScope($scopes);
        $key = 'api:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Límite de peticiones excedido',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        RateLimiter::hit($key);
        $response = $next($request);

        $response->header('X-RateLimit-Limit', $limit);
        $response->header('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit));
        $response->header('X-RateLimit-Reset', RateLimiter::availableIn($key));

        return $response;
    }

    private function getLimitByScope(array $scopes): int
    {
        if (in_array('admin', $scopes)) return 2000;
        if (in_array('desarrollo', $scopes)) return 1000;
        if (in_array('produccion', $scopes)) return 1500;
        return 500;
    }
}
```

#### Registro en bootstrap/app.php
```php
use App\Http\Middleware\ClientRateLimiting;

$middleware->alias([
    'client.limit' => ClientRateLimiting::class,
]);
```

### 2. CORS Configuration

#### Middleware CORS
```php
// app/Http/Middleware/Cors.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');

        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(200);
        }

        return $response;
    }
}
```

#### Registro en bootstrap/app.php
```php
use App\Http\Middleware\Cors;

$middleware->alias([
    'cors' => Cors::class,
]);
```

### 3. Sistema de Logging

#### Configuración de Logging
```php
// config/logging.php
return [
    'channels' => [
        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api/api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'formatter' => JsonFormatter::class,
        ],
        
        'api_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api/errors.log'),
            'level' => 'error',
            'days' => 30,
            'formatter' => JsonFormatter::class,
        ],
    ],
];
```

#### Servicio de Logging
```php
// app/Services/ApiLogger.php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Exception;

class ApiLogger
{
    public function logRequest(Request $request, string $level = 'info'): void
    {
        $input = $request->except(['password', 'password_confirmation']);
        
        $logData = [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'user_scopes' => $request->user()?->scopes,
            'headers' => $this->getRelevantHeaders($request),
            'payload' => $input,
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('api')->$level('API Request', $logData);
    }

    public function logResponse($response, Request $request, float $duration = null): void
    {
        $logData = [
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'duration' => $duration ? round($duration, 3) . 's' : null,
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($response->status() >= 400) {
            $logData['content'] = json_decode($response->getContent(), true);
            Log::channel('api_errors')->error('API Error Response', $logData);
        } else {
            Log::channel('api')->info('API Response', $logData);
        }
    }

    private function getRelevantHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        
        $sensitiveHeaders = ['authorization', 'cookie'];
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }

        return $headers;
    }
}
```

### 4. Estructura de Rutas Actual

```php
// routes/api.php
Route::middleware(['cors', 'api.logging'])->group(function () {
    // Rutas públicas
    Route::post('/create-first-admin', [AdminController::class, 'createFirstAdmin']);
    Route::post('/oauth/token', [AuthController::class, 'issueToken'])
        ->name('passport.token');

    // Rutas administrativas con rate limiting
    Route::middleware(['auth:api', 'scope:admin', 'client.limit'])->group(function () {
        Route::post('/create-user', [AdminController::class, 'createUser']);
    });

    // Rutas generales con rate limiting
    Route::middleware(['auth:api', 'scope:admin,user', 'client.limit'])->group(function () {
        Route::get('/profile', function () {
            return auth()->user();
        });
    });
});

// Manejo de OPTIONS para CORS
Route::options('/{any}', function () {
    return response()->json(null, 200);
})->where('any', '.*');
```

### Ejemplos de Uso

#### 1. Creación de Primer Admin
```bash
curl -X POST http://tu-api.com/api/create-first-admin \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin Usuario",
    "email": "admin@ejemplo.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

#### 2. Obtención de Token
```bash
curl -X POST http://tu-api.com/api/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "2",
    "client_secret": "tu-client-secret",
    "username": "admin@ejemplo.com",
    "password": "password123",
    "scope": "admin"
  }'
```

#### 3. Crear Usuario (Con Token)
```bash
curl -X POST http://tu-api.com/api/create-user \
  -H "Authorization: Bearer {tu-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nuevo Usuario",
    "email": "usuario@ejemplo.com",
    "password": "password123",
    "password_confirmation": "password123",
    "scopes": ["user"]
  }'
```

### Ejemplos de Logs Generados

#### Log de Petición Exitosa
```json
{
    "timestamp": "2024-11-21T15:30:45+00:00",
    "level": "info",
    "message": "API Request",
    "context": {
        "ip": "192.168.1.1",
        "method": "POST",
        "url": "https://api.example.com/api/create-user",
        "user_id": 1,
        "user_email": "admin@example.com",
        "user_scopes": ["admin"],
        "duration": "0.123s",
        "status": 200
    }
}
```

#### Log de Error
```json
{
    "timestamp": "2024-11-21T15:31:00+00:00",
    "level": "error",
    "message": "API Error Response",
    "context": {
        "url": "https://api.example.com/api/create-user",
        "status": 422,
        "duration": "0.089s",
        "content": {
            "message": "Error de validación",
            "errors": {
                "email": ["El correo ya está registrado"]
            }
        }
    }
}
```

### Instrucciones de Mantenimiento

1. **Rotación de Logs**
    - Los logs normales se mantienen por 14 días
    - Los logs de errores se mantienen por 30 días
    - Rotación automática diaria

2. **Monitoreo**
    - Revisar `storage/logs/api/errors.log` para errores
    - Monitorear rate limiting en los logs
    - Verificar patrones de uso en `api.log`

3. **Limpieza**
    - Los logs antiguos se eliminan automáticamente
    - Período de retención configurable en `logging.php`

4. **Backup**
    - Recomendado hacer backup diario de logs
    - Mantener respaldo de logs de error por 90 días

### Próximos Pasos
- [ ] Implementar documentación Swagger
- [ ] Configurar sistema de facturación
- [ ] Implementar conexión con SRI
- [ ] Agregar tests automatizados
- [ ] Implementar sistema de notificaciones
- [ ] Mejorar sistema de monitoreo

### Contacto

- **Empresa**: MACROGRAM CIA LTDA.
- **Ubicación**: Guayaquil - Daule - Ecuador
- **Email**: soporte@macrobilling.com
- **Teléfono**: +593 4 123 4567

---

**© 2024 MACROGRAM CIA LTDA. Todos los derechos reservados.**
