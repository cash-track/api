<?php

declare(strict_types=1);

namespace App\Request;

use Spiral\Filters\Filter;

class RefreshTokenRequest extends Filter
{
    protected const SCHEMA = [
        'accessToken' => 'data:accessToken',
    ];

    protected const VALIDATES = [
        'accessToken' => ['type::notEmpty'],
    ];

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return (string) $this->getField('accessToken');
    }
}
