<?php

/**
 * @see \App\Config\CdnConfig
 */
declare(strict_types = 1);

return [
    'host'   => env('CDN_HOST'),
    'bucket' => env('CDN_BUCKET'),
];
