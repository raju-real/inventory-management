<?php

namespace App\Services;

abstract class BaseService
{
    protected const PER_PAGE = 15;

    /** Cache key of a paginated list: unique per filters and page. */
    protected function listKey(array $filters): string
    {
        return 'list:' . md5(json_encode(array_filter($filters, fn ($v) => $v !== null && $v !== '')) . '|' . request('page', 1) . '|' . request()->path());
    }

    protected function applyStatusFilter($query, array $filters): void
    {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }
    }
}
