<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class RedisConfig extends InjectableConfig
{
    public const string CONFIG = 'redis';

    protected array $config = [
        'connection' => '',
        'timeout' => '',
        'retry_interval' => '',
        'retry_timeout' => '',
        'prefix' => '',
        'max_retries' => '',
    ];

    public function getHost(): string
    {
        return (string) parse_url($this->config['connection'], PHP_URL_HOST);
    }

    public function getPort(): int
    {
        return (int) parse_url($this->config['connection'], PHP_URL_PORT);
    }

    public function getTimeout(): float
    {
        return (float) $this->config['timeout'];
    }

    public function getRetryInterval(): int
    {
        return (int) $this->config['retry_interval'];
    }

    public function getRetryTimeout(): float
    {
        return (float) $this->config['retry_timeout'];
    }

    public function getPrefix(): string
    {
        return (string) $this->config['prefix'];
    }

    public function getMaxRetries(): int
    {
        return (int) $this->config['max_retries'];
    }
}
