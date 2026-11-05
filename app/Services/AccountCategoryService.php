<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Shared logic of expense categories and income categories. */
abstract class AccountCategoryService extends BaseService
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    public function paginate(array $filters)
    {
        $model = $this->model();

        return $model::rememberCache($this->listKey($filters), function () use ($model, $filters) {
            $query = $model::withCount('entries')->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(Model $category, array $data): Model
    {
        $category->update($data);

        return $category;
    }

    public function delete(Model $category): void
    {
        if ($category->entries()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category is used by entries. Deactivate it instead.']);
        }

        $category->delete();
    }
}
