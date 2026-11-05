<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Filters arrive as unique_ids in the URL; the services filter on internal keys.
     * A value that matches nothing becomes -1, which correctly returns no rows.
     */
    protected function resolveIds(array $filters, array $references): array
    {
        foreach ($references as $field => $model) {
            if (! empty($filters[$field])) {
                $filters[$field] = $model::where('unique_id', $filters[$field])->value('id') ?? -1;
            }
        }

        return $filters;
    }

    /** Maps the standard controller methods to the permission of a menu. */
    protected function accessResource(string $menuKey): void
    {
        $this->middleware("access:{$menuKey},view")->only(['index', 'show', 'pdf', 'email', 'labels']);
        $this->middleware("access:{$menuKey},create")->only(['create', 'store']);
        $this->middleware("access:{$menuKey},edit")->only(['edit', 'update']);
        $this->middleware("access:{$menuKey},delete")->only(['destroy', 'cancel']);
    }
}
