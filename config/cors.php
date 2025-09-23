<?php
$env = env('APP_ENV', 'production');

if ($env === 'local') {
    $allowedOrigins = ['http://localhost:3000','http://localhost:5173'];
    $allowedMethods = ['*'];
    $allowedHeaders = ['*'];
} else {
    $allowedOrigins = ['https://miapp.com'];
    $allowedMethods = ['GET','POST','PUT','DELETE'];
    $allowedHeaders = ['Content-Type','X-Requested-With','Authorization'];
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => $allowedMethods,
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => $allowedHeaders,
    'exposed_headers' => ['Authorization', 'Set-Cookie'],
    'max_age' => 3600,
    'supports_credentials' => true,
];