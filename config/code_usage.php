<?php

return [
    'enabled' => env('CODE_USAGE_ENABLED', false),
    'sample_rate' => (float) env('CODE_USAGE_SAMPLE_RATE', 0.25),
    'throttle_per_minute' => (int) env('CODE_USAGE_THROTTLE_PER_MINUTE', 5),
    'include_namespaces' => array_filter(array_map('trim', explode(',', env('CODE_USAGE_INCLUDE_NAMESPACES', 'App\\')))),
    'exclude_namespaces' => array_filter(array_map('trim', explode(',', env('CODE_USAGE_EXCLUDE_NAMESPACES', 'App\\Providers,App\\Console')))),
    'retention_days' => (int) env('CODE_USAGE_RETENTION_DAYS', 90),
    'queue_connection' => env('CODE_USAGE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'queue_name' => env('CODE_USAGE_QUEUE_NAME', 'default'),
    'meta_enabled' => filter_var(env('CODE_USAGE_META_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'sampling_whitelist' => array_filter(explode(',', env('CODE_USAGE_SAMPLING_WHITELIST', 'App\\Http\\Controllers\\HealthController'))),
    'meta_keys' => [
        'route' => 'route_name',
        'command' => 'command_signature',
        'queue' => 'queue_name',
    ],
];
