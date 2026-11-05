<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Validation\ValidationException;

class UnitService extends BaseService
{
    public function paginate(array $filters)
    {
        return Unit::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Unit::withCount('products')->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit;
    }

    public function delete(Unit $unit): void
    {
        if ($unit->products()->exists()) {
            throw ValidationException::withMessages(['unit' => 'This unit is used by products. Deactivate it instead.']);
        }

        $unit->delete();
    }
}
