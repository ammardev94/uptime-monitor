<?php

namespace App\Services;

class UrlHelper
{
    public static function normalize(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_XML1));
        $url = preg_replace('/#.*$/', '', $url);
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }
        $parts = parse_url($url);
        if (! $parts || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return "$scheme://$host$port$path$query";
    }

    /** Resolve a (possibly relative) href against a base URL. */
    public static function resolve(string $href, string $base): ?string
    {
        $href = trim($href);
        if ($href === '' || preg_match('#^(mailto:|tel:|javascript:|data:|\#)#i', $href)) {
            return null;
        }
        if (preg_match('#^https?://#i', $href)) {
            return self::normalize($href);
        }
        $b = parse_url($base);
        $origin = $b['scheme'].'://'.$b['host'].(isset($b['port']) ? ':'.$b['port'] : '');
        if (str_starts_with($href, '//')) {
            return self::normalize($b['scheme'].':'.$href);
        }
        if (str_starts_with($href, '/')) {
            return self::normalize($origin.$href);
        }
        $dir = preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/');

        return self::normalize($origin.$dir.$href);
    }

    public static function sameHost(string $url, string $host): bool
    {
        $h = strtolower((string) parse_url($url, PHP_URL_HOST));
        $strip = fn ($x) => preg_replace('/^www\./', '', $x);

        return $strip($h) === $strip($host);
    }

    public static function looksLikePage(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return ! preg_match('/\.(jpe?g|png|gif|webp|svg|ico|pdf|zip|rar|mp4|mp3|mov|avi|css|js|json|xml|txt|woff2?|ttf|eot|docx?|xlsx?|pptx?)$/', $path)
            && ! preg_match('#/(wp-admin|wp-json|cart|checkout|my-account|feed)(/|$)#', $path);
    }
}
