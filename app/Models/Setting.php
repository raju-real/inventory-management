<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use PDOException;

class Setting extends Model
{
    use HasModelCache, HasUniqueId;

    protected $fillable = ['key', 'value'];

    public static function allCached(): array
    {
        return static::rememberCache('all', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function valueOf(string $key, $default = null)
    {
        try {
            $value = static::allCached()[$key] ?? null;
        } catch (QueryException | PDOException $e) {
            // No database or table yet, for example during composer install or before migrating.
            return $default;
        }

        return $value === null || $value === '' ? $default : $value;
    }
}
