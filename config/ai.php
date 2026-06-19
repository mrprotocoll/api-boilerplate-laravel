<?php

declare(strict_types=1);

return [
    'default_provider' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'temperature' => env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 120),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
            'temperature' => env('ANTHROPIC_TEMPERATURE', 0.7),
            'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 2000),
            'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'temperature' => env('GEMINI_TEMPERATURE', 0.7),
            'max_tokens' => env('GEMINI_MAX_TOKENS', 2000),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
    ],

    'assistant' => [
        'runtime' => [
            'max_tool_steps' => env('AI_MAX_TOOL_STEPS', 3),
            'max_tool_calls_per_request' => env('AI_MAX_TOOL_CALLS_PER_REQUEST', 5),
            'request_timeout' => env('AI_REQUEST_TIMEOUT', 30),
        ],

        'tool_result_synthesis' => [
            'enabled' => env('AI_TOOL_RESULT_SYNTHESIS', true),
            'max_tokens' => env('AI_TOOL_RESULT_SYNTHESIS_MAX_TOKENS', 700),
            'timeout' => env('AI_TOOL_RESULT_SYNTHESIS_TIMEOUT', 15),
        ],

        'limits' => [
            'daily_user_message_limit' => env('AI_DAILY_USER_MESSAGE_LIMIT'),
            'daily_user_cost_limit' => env('AI_DAILY_USER_COST_LIMIT'),
            'daily_admin_message_limit' => env('AI_DAILY_ADMIN_MESSAGE_LIMIT'),
            'daily_admin_cost_limit' => env('AI_DAILY_ADMIN_COST_LIMIT'),
        ],

        'navigation' => [
            'routes' => [
                'home' => '/',
                'dashboard' => '/dashboard',
                'profile' => '/profile',
                'settings' => '/settings',
            ],
        ],
    ],

    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'channel' => env('AI_LOGGING_CHANNEL', 'ai'),
    ],

    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', false),
        'ttl' => env('AI_CACHE_TTL', 3600),
        'prefix' => env('AI_CACHE_PREFIX', 'ai_'),
    ],
];
