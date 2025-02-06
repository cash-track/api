<?php

declare(strict_types=1);

if (! function_exists('str_lower')) {

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    function str_lower($value) {
        return mb_strtolower($value, 'UTF-8');
    }
}

if (! function_exists('str_rand')) {

    /**
     * Generate random string of given $length characters
     *
     * @param int $length
     * @return string
     * @throws \Exception
     */
    function str_rand(int $length = 16): string {
        return bin2hex(random_bytes($length));
    }
}
