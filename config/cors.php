<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://superapps-gsa.vercel.app',
        'http://localhost:3000',
        'https://localhost:3000',
        'http://gsa-new.test',
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.vercel\.app$/',
    ],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'Origin',
        'Cache-Control',
        'X-Auth-Token',
    ],
    'exposed_headers' => [
        'Authorization',
        'Content-Length', 
        'X-Auth-Token'
    ],
    'max_age' => 86400,
    'supports_credentials' => true,
];