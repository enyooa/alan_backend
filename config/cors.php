<?php

return [
    'paths' => ['api/*'], // Ensure your API endpoints are included
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Replace '*' with specific domain(s) in production
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

