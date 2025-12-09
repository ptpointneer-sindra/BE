<?php
return [
    'paths' => ['api/*', 'api/documentation', 'api/docs', 'forgot-password/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'https://sindra.okkyprojects.com',
        'https://sindra.online'
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];