<?php

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

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

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // Security events logging
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 90, // Keep security logs longer
            'replace_placeholders' => true,
        ],

        // User activity logging
        'activity' => [
            'driver' => 'daily',
            'path' => storage_path('logs/activity.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // Database query logging
        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => env('DB_LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // Performance metrics logging
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // Error-only channel
        'errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/errors.log'),
            'level' => 'error',
            'days' => 60,
            'replace_placeholders' => true,
        ],

        // Slack notifications for critical errors (optional)
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('APP_NAME', 'Laravel'),
            'emoji' => ':boom:',
            'level' => env('LOG_SLACK_LEVEL', 'critical'),
        ],

        // Email notifications for critical errors (optional)
        'mail' => [
            'driver' => 'mail',
            'to' => [
                'address' => env('LOG_MAIL_TO'),
                'name' => env('LOG_MAIL_NAME', 'Laravel Log'),
            ],
            'subject' => 'Critical Error in ' . env('APP_NAME'),
            'level' => 'critical',
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [
                Monolog\Processor\PsrLogMessageProcessor::class,
            ],
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

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Configuration
    |--------------------------------------------------------------------------
    */

    // Enable automatic database query logging
    'log_queries' => env('LOG_QUERIES', false),

    // Performance thresholds for automatic warnings
    'performance_thresholds' => [
        'response_time' => env('LOG_RESPONSE_TIME_THRESHOLD', 2000), // 2 seconds
        'memory_usage' => env('LOG_MEMORY_THRESHOLD', 128 * 1024 * 1024), // 128 MB
        'query_time' => env('LOG_QUERY_TIME_THRESHOLD', 1000), // 1 second
    ],

    // Context data to include in all logs
    'default_context' => [
        'app_version' => env('APP_VERSION', '1.0.0'),
        'server' => env('SERVER_NAME', gethostname()),
    ],

];
