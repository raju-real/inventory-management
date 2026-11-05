<?php

namespace App\Models\Concerns;

/**
 * Public identifier of a record: 10 random lowercase letters. URLs, forms and JSON only ever
 * use this value. The numeric primary key stays inside the database for joins.
 */
trait HasUniqueId
{
    public static function bootHasUniqueId(): void
    {
        static::creating(function ($model) {
            $model->unique_id = $model->unique_id ?: static::generateUniqueId();
        });
    }

    public static function generateUniqueId(): string
    {
        do {
            $id = '';
            for ($i = 0; $i < 10; $i++) {
                $id .= chr(random_int(97, 122));
            }
        } while (static::where('unique_id', $id)->exists());

        return $id;
    }

    /** Route model binding finds records by unique_id, so numeric URLs simply do not exist. */
    public function getRouteKeyName()
    {
        return 'unique_id';
    }
}
