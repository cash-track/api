<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Attribute\Input\Data;
use Spiral\Filters\Model\Filter;

class LogoutRequest extends Filter
{
    #[Data]
    public string $refreshToken = '';
}
