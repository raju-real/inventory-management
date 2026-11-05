<?php

namespace App\Models\Concerns;

use App\Support\VersionedCache;
use Closure;

/**
 * Model level cache. ModelCacheObserver flushes the groups whenever a model changes.
 * A model can declare `protected static array $cacheDependents` to also flush other
 * groups, e.g. renaming a category must refresh cached product lists.
 */
trait HasModelCache
{
    public static function cacheGroup(): string
    {
        return static::$cacheGroupName ?? (new static())->getTable();
    }

    public static function rememberCache(string $key, Closure $callback, int $ttl = VersionedCache::TTL)
    {
        return VersionedCache::remember(static::cacheGroup(), $key, $callback, $ttl);
    }

    public static function flushModelCache(): void
    {
        VersionedCache::flush(static::cacheGroup());
        foreach (static::$cacheDependents ?? [] as $group) {
            VersionedCache::flush($group);
        }
    }
}
