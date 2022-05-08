<?php
/**
 * @see \App\Config\FirebaseConfig
 */
declare(strict_types = 1);

return [
    'databaseUri'             => env('FIREBASE_DATABASE_URI'),
    'storageBucket'           => env('FIREBASE_STORAGE_BUCKET'),

    /**
     * Fill those values from Firebase Admin SDK credentials file.
     */
    'projectId'               => env('FIREBASE_PROJECT_ID'),
    'privateKeyId'            => env('FIREBASE_PRIVATE_KEY_ID'),
    'privateKey'              => env('FIREBASE_PRIVATE_KEY'),
    'clientEmail'             => env('FIREBASE_CLIENT_EMAIL'),
    'clientId'                => env('FIREBASE_CLIENT_ID'),
    'authUri'                 => env('FIREBASE_AUTH_URI'),
    'tokenUri'                => env('FIREBASE_TOKEN_URI'),
    'authProviderX509CertUrl' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL'),
    'clientX509CertUrl'       => env('FIREBASE_CLIENT_X509_CERT_URL'),
];
