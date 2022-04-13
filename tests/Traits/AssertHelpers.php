<?php

declare(strict_types=1);

namespace Tests\Traits;

trait AssertHelpers
{
    /**
     * Assert $haystack array contain $needle value by given $key dot based array path
     *
     * @param mixed $needle
     * @param array $haystack
     * @param string $key
     * @return void
     */
    protected function assertArrayContains(mixed $needle, array $haystack, string $key): void
    {
        $this->assertContains($needle, data_get($haystack, $key, []));
    }
}
