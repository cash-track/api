<?php

declare(strict_types = 1);

return [
    'url' => env('APP_URL'),
    'website_url' => env('WEBSITE_URL'),
    'web_app_url' => env('WEB_APP_URL'),

    'email_confirmation_link' => '/email/confirm/{token}',
    'password_reset_link' => '/password/reset/{code}',
    'wallet_link' => '/wallets/{wallet}',

    'db_encrypter_key' => env('DB_ENCRYPTER_KEY'),
];
