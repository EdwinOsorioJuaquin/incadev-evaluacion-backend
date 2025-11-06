<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths that should be accessible for CORS
    |--------------------------------------------------------------------------
    |
    | Define the routes or paths where CORS headers should be applied.
    | Commonly, this includes API routes and Sanctum cookie endpoints.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Specify which HTTP methods are allowed for cross-origin requests.
    | You can use ['*'] to allow all.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Here you list all the front-end URLs that can access your backend.
    | During development, include localhost and 127.0.0.1.
    |
    */

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://instituto.cetivirgendelapuerta.com', // producción (opcional)
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | These are the headers allowed in requests. Usually you keep ['*'].
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers that the browser should make accessible to JavaScript.
    |
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Indicates how long (in seconds) the results of a preflight request
    | can be cached by the browser.
    |
    */

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Should be true if you need cookies or Authorization headers.
    |
    */

    'supports_credentials' => true,

];
