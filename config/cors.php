<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173')),
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    // 24h: evita que el navegador repita el preflight OPTIONS en cada pedido.
    'max_age' => 86400,
    'supports_credentials' => true,
];