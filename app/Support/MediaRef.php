<?php

namespace App\Support;

/** Validates an image URL chosen in the admin media library before a controller stores it (only our own library files are accepted). */
class MediaRef
{
    public static function clean(mixed $url): ?string
    {
        if (! is_string($url) || strlen($url) > 1000 || ! preg_match('#^https?://#i', $url)) return null;
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (str_contains($path, '..')) return null;
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(url('/'), PHP_URL_HOST);
        if ($host !== $appHost && $host !== request()->getHost()) return null;

        return preg_match('#/(uploads/|storage/editor/)#', $path) ? $url : null;
    }

    /** @return string[] */
    public static function cleanMany(mixed $urls): array
    {
        return array_values(array_unique(array_filter(array_map([self::class, 'clean'], is_array($urls) ? $urls : []))));
    }
}
