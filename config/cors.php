<?php

return [
    /*
    | Los caminos que habilitamos. Necesitamos api/* y el de las cookies de Sanctum.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Aquí le damos permiso al puerto de Vite/React
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // CRÍTICO: Debe estar en true para que funcionen las cookies de sesión (stateful)
    'supports_credentials' => true, 
];