<?php

return [
    'paths' => ['api/*'],  // Sacamos sanctum/csrf-cookie, no lo necesitamos

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,  // Sin cookies, esto va en false
];