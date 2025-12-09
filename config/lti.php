<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LTI 1.3 Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LTI 1.3 integration with Canvas LMS
    |
    */

    'client_id' => env('LTI_CLIENT_ID'),
    'deployment_id' => env('LTI_DEPLOYMENT_ID'),
    'issuer' => env('LTI_ISSUER', 'https://canvas.instructure.com'),
    'auth_url' => env('LTI_AUTH_URL', 'https://canvas.instructure.com/api/lti/authorize_redirect'),
    'jwks_url' => env('LTI_JWKS_URL', 'https://canvas.instructure.com/api/lti/security/jwks'),

    /*
    |--------------------------------------------------------------------------
    | RSA Keys
    |--------------------------------------------------------------------------
    |
    | Paths to the RSA private and public keys used for JWT signing
    |
    */

    'private_key' => storage_path('lti-private.key'),
    'public_key' => storage_path('lti-public.key'),

];
