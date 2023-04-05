<?php

use Symfony\Component\Translation\Dumper;
use Symfony\Component\Translation\Loader;

return [
    'locale'         => env('LOCALE', 'en'),
    'fallbackLocale' => env('LOCALE', 'en'),
    'directory'      => directory('locale'),
    'autoRegister'   => env('DEBUG', true),

    // available locale loaders (the key is extension)
    'loaders'        => [
        'php'  => Loader\PhpFileLoader::class,
        'po'   => Loader\PoFileLoader::class,
        'csv'  => Loader\CsvFileLoader::class,
        'json' => Loader\JsonFileLoader::class
    ],

    // export methods
    'dumpers'        => [
        'php'  => Dumper\PhpFileDumper::class,
        'po'   => Dumper\PoFileDumper::class,
        'csv'  => Dumper\CsvFileDumper::class,
        'json' => Dumper\JsonFileDumper::class,
    ],

    'domains'        => [
        // by default, we can store all messages in one domain
        'messages' => ['*']
    ]
];
