<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class DamageRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'damage_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'unique_id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'items.*.reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return ['items.*.product_id' => 'product', 'items.*.quantity' => 'damage quantity', 'items.*.reason' => 'damage reason'];
    }

    public function messages()
    {
        return ['items.required' => 'Add at least one product.', 'items.min' => 'Add at least one product.'];
    }

    protected function references(): array
    {
        return [
            'items.*.product_id' => \App\Models\Product::class,
        ];
    }
}
