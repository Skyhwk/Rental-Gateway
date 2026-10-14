<?php

namespace App\Support;

class HostnameNormalizer
{
    public static function normalize($hostname)
    {
        $hostname = strtolower(trim((string) $hostname));

        if (strpos($hostname, '://') !== false) {
            $parsed = parse_url($hostname, PHP_URL_HOST);
            $hostname = $parsed ?: $hostname;
        }

        $hostname = rtrim($hostname, '.');

        return $hostname;
    }

    public static function isValid($hostname)
    {
        if ($hostname === '' || strlen($hostname) > 253) {
            return false;
        }

        return (bool) preg_match('/^(?=.{1,253}$)(?!-)([a-z0-9-]{1,63}(?<!-)\.)*[a-z0-9-]{1,63}(?<!-)$/', $hostname);
    }

    /**
     * @param  string  $input  newline or comma separated
     * @return array<int, string>
     */
    public static function parseList($input)
    {
        $parts = preg_split('/[\r\n,]+/', (string) $input, -1, PREG_SPLIT_NO_EMPTY);
        $hosts = [];

        foreach ($parts as $part) {
            $normalized = self::normalize($part);
            if ($normalized !== '') {
                $hosts[] = $normalized;
            }
        }

        return array_values(array_unique($hosts));
    }
}
