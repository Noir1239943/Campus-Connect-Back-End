<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The frontend (Vite dev server, e.g. http://localhost:5173) calls this
    | API from a different origin than http://localhost:8000, so the api/*
    | routes need CORS enabled. Auth is bearer-token based (see AuthController
    | and bootstrap/app.php), not cookie-based, so supports_credentials stays
    | false and no specific origin allowlist is required for local dev.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
