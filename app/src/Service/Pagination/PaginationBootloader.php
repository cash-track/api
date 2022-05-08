<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace App\Service\Pagination;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Http\HttpBootloader;

final class PaginationBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        HttpBootloader::class
    ];

    protected const SINGLETONS = [
        PaginationProviderInterface::class => PaginationFactory::class
    ];
}
