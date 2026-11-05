<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Group based caching that works on every Cache driver (file, database, redis, ...).
 * Cache tags are not supported by the file driver, so each group keeps a version
 * string inside its keys. Flushing a group changes the version; the old entries
 * are never read again and simply expire.
 */
class VersionedCache
{
    public const TTL = 3600;

    public static function remember(string $group, string $key, Closure $callback, int $ttl = self::TTL)
    {
        return Cache::remember("{$group}:" . static::version($group) . ":{$key}", $ttl, $callback);
    }

    public static function flush(string $group): void
    {
        Cache::forget("{$group}:version");
    }

    private static function version(string $group): string
    {
        return Cache::rememberForever("{$group}:version", fn () => Str::random(12));
    }
}
