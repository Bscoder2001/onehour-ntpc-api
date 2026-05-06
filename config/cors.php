<?php

/*
| CORS is handled globally by App\Http\Middleware\CorsMiddleware (see bootstrap/app.php).
| Laravel HandleCors is removed so all routes get consistent headers. This file is
| kept for reference / tooling only unless you re-enable HandleCors.
*/

$defaultOrigins = implode(',', [
    'http://localhost:8000',
    'http://127.0.0.1:8000',
    'http://localhost:5500',
    'http://127.0.0.1:5500',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:4173',
    'http://127.0.0.1:4173',
]);

$origins = env('CORS_ALLOWED_ORIGINS', $defaultOrigins);
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $origins))));

return [

    'paths' => [
        'api/*',
        'users/*',
        'v2users/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    /*
    | true: browser may send cookies (admin login). Request Origin must match
    | an entry in allowed_origins (not *). Add your frontend URL to .env
    | CORS_ALLOWED_ORIGINS if needed.
    */
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
