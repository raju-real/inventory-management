<?php

namespace App\Observers;

use App\Support\VersionedCache;
use Illuminate\Database\Eloquent\Model;

class ModelCacheObserver
{
    /** Flush only after the DB transaction commits so no reader can re-cache uncommitted data. */
    public $afterCommit = true;

    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        $model::flushModelCache();
        VersionedCache::flush('dashboard');
    }
}
