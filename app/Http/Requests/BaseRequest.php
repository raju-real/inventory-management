<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Forms send unique_ids. After validation, the fields named in references() become the
 * internal keys, so the services work with ordinary foreign keys.
 */
abstract class BaseRequest extends FormRequest
{
    /** ['supplier_id' => Supplier::class, 'items.*.product_id' => Product::class] */
    protected function references(): array
    {
        return [];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        foreach ($this->references() as $field => $model) {
            if (str_contains($field, '.*.')) {
                [$list, $column] = explode('.*.', $field);
                $ids = $model::whereIn('unique_id', array_filter(array_column($data[$list] ?? [], $column)))->pluck('id', 'unique_id');
                foreach ($data[$list] ?? [] as $index => $row) {
                    $data[$list][$index][$column] = $ids[$row[$column] ?? ''] ?? null;
                }
            } elseif (! empty($data[$field])) {
                $data[$field] = $model::where('unique_id', $data[$field])->value('id');
            }
        }

        return data_get($data, $key, $default);
    }
}
