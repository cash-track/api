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
        $debug = "Path '{$key}' of an array " . print_r($haystack, true);

        $value = data_get($haystack, $key, []);

        if (is_array($value)) {
            $this->assertContains($needle, $value, $debug);
            return;
        }

        $this->assertEquals($needle, $value, $debug);
    }

    /**
     * Assert $haystack array not contain $needle value by given $key dot based array path
     *
     * @param mixed $needle
     * @param array $haystack
     * @param string $key
     * @return void
     */
    protected function assertArrayNotContains(mixed $needle, array $haystack, string $key): void
    {
        $this->assertNotContains(
            $needle,
            data_get($haystack, $key, []),
            "Path '{$key}' of an array " . print_r($haystack, true)
        );
    }
}
