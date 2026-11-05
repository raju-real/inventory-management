<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Validation\ValidationException;

class CategoryService extends BaseService
{
    public function paginate(array $filters)
    {
        return Category::rememberCache($this->listKey($filters), function () use ($filters) {
            $query = Category::withCount('products')->latest('id');
            $this->applyStatusFilter($query, $filters);

            if (! empty($filters['search'])) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            }

            return $query->paginate(static::PER_PAGE)->withQueryString();
        });
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages(['category' => 'This category is used by products. Deactivate it instead.']);
        }

        $category->delete();
    }
}
