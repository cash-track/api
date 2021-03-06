<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'stderr');

require __DIR__ . '/vendor/autoload.php';

$app = \App\App::init(['root' => __DIR__]);

if ($app != null) {
    $app->serve();
}
