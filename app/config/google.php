<?php
/**
 * @see \App\Config\GoogleApiConfig
 */
declare(strict_types = 1);

return [
    /**
     * Fill those values from Google Api credentials file.
     */
    'clientId'                => env('GOOGLE_API_CLIENT_ID'),
    'projectId'               => env('GOOGLE_API_PROJECT_ID'),
    'authUri'                 => env('GOOGLE_API_AUTH_URI'),
    'tokenUri'                => env('GOOGLE_API_TOKEN_URI'),
    'authProviderX509CertUrl' => env('GOOGLE_API_AUTH_PROVIDER_X509_CERT_URL'),
    'clientSecret'            => env('GOOGLE_API_CLIENT_SECRET'),
    'redirectUris'            => [env('GOOGLE_API_REDIRECT_URI')],
];
