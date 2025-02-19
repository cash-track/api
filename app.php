<?php

declare(strict_types=1);

use App\App;

// If you forgot to configure some of this in your php.ini file,
// then don't worry, we will set the standard environment
// settings for you.

\mb_internal_encoding('UTF-8');
\error_reporting(E_ALL | E_DEPRECATED);
\ini_set('display_errors', 'stderr');

require __DIR__ . '/vendor/autoload.php';


// Initialize shared container, bindings, directories and etc.
$app = App::create(['root' => __DIR__])->run();

if ($app === null) {
    exit(255);
}

$code = (int) $app->serve();

exit($code);
