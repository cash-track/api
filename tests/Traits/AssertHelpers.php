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
        $debug = "Looking for value '{$needle}' by path '{$key}' of an array " . print_r($haystack, true);

        $value = data_get($haystack, $key, []);

        if (is_array($value)) {
            $this->assertContains($needle, $value, $debug);
            return;
        }

        // unit test float weird precision fix
        if (is_float($value)) {
            $value = (string) $value;
            $needle = (string) $needle;
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
        $debug = "Looking for value '{$needle}' by path '{$key}' of an array " . print_r($haystack, true);

        $value = data_get($haystack, $key, []);

        if (is_array($value)) {
            $this->assertNotContains($needle, $value, $debug);
            return;
        }

        $this->assertNotEquals($needle, $value, $debug);
    }
}
