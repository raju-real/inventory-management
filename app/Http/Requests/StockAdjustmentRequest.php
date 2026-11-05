<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'adjustment_date' => ['required', 'date_format:Y-m-d'],
            'product_id' => ['required', Rule::exists('products', 'unique_id')],
            'type' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        return ['product_id' => 'product'];
    }

    protected function references(): array
    {
        return [
            'product_id' => \App\Models\Product::class,
        ];
    }
}
