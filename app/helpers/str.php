<?php

declare(strict_types=1);

use voku\helper\ASCII;

if (! function_exists('str_ascii')) {

    /**
     * Transliterate a UTF-8 value to ASCII.
     *
     * @param  string  $value
     * @param  string  $language
     * @return string
     */
    function str_ascii($value, $language = 'en')
    {
        return ASCII::to_ascii((string) $value, $language);
    }
}

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

if (! function_exists('str_slug')) {

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $title
     * @param  string  $separator
     * @param  string|null  $language
     * @return string
     */
    function str_slug($title, $separator = '-', $language = 'en'): string {
        $title = $language ? str_ascii($title, $language) : $title;

        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $separator.'at'.$separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', str_lower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

        return trim($title, $separator);
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
