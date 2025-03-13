<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;


return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],
        'custom_log' => [
            'driver' => 'single', // Para un solo archivo de log
            'path' => storage_path('logs/custom_log.log'), // Ruta del archivo de log
            'level' => 'info', // El nivel de log (info, error, etc.)
        ],
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
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
        'facturas' => [
            'driver' => 'daily',  // Usa el controlador 'daily' para crear un archivo por día
            'path' => storage_path('logs/facturas/logs_facturas_'). date('Y-m-d') . '.log', // Ruta donde se guardarán los logs de facturas
            'level' => 'info',  // Nivel de log
            'days' => 30, // Cuántos días quieres conservar los logs
        ],

        // Canal para Notas de Crédito
        'notas_credito' => [
            'driver' => 'daily',
            'path' => storage_path('logs/notas_credito/logs_notas_credito_'). '.log',
            'level' => 'info',
            'days' => 30,
        ],

        // Canal para Guias
        'guias' => [
            'driver' => 'daily',
            'path' => storage_path('logs/guias/logs_guias_'). '.log',
            'level' => 'info',
            'days' => 30,
        ],
        // Canal para Liquidacion
        'liquidacion' => [
            'driver' => 'daily',
            'path' => storage_path('logs/liquidacion/logs_liquidacion_').  '.log',
            'level' => 'info',
            'days' => 30,
        ],
        // Canal para retenciones
        'retenciones' => [
            'driver' => 'daily',
            'path' => storage_path('logs/retenciones/logs_retenciones_'). '.log',
            'level' => 'info',
            'days' => 30,
        ],
        'general' => [
            'driver' => 'daily',
            'path' => storage_path('logs/general/logs_general_'). '.log',
            'level' => 'info',
            'days' => 30,
        ],

    ],

];
