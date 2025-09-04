<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://superapps-gsa.vercel.app'],  // For production, specify your actual domains
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Length', 'X-Auth-Token'],
    'max_age' => 60 * 60 * 24,  // 24 hours in seconds
    'supports_credentials' => false,
];
