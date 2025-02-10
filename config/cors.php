<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],//'storage/*'
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // You can restrict to specific domains for better security
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'exposed_headers' => false,
    'max_age' => 0,
    'supports_credentials' => false,
];

