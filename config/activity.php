<?php

return [
    // Enable activity logging in console commands
    'log_in_console' => false,

    // Default retention period for activities (days)
    'retention_days' => 90,

    // Automatically log model events
    'auto_log_models' => [
        // Add model classes here
        // App\Models\User::class,
        // App\Models\Order::class,
    ],

    // Events to ignore globally
    'ignore_events' => [
        'retrieved',
        'saving',
        'saved',
    ],
];
