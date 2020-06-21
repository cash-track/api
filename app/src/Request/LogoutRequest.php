<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Filter;

class LogoutRequest extends Filter
{
    protected const SCHEMA = [
        'refreshToken' => 'data:refreshToken',
    ];

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return (string) $this->getField('refreshToken');
    }
}
