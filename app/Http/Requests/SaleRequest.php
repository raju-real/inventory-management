<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SaleRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sale_date' => ['required', 'date_format:Y-m-d'],
            'customer_id' => ['required', Rule::exists('customers', 'unique_id')->where('status', true)],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,bank,card,mobile,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'unique_id')->where('status', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function attributes()
    {
        return ['customer_id' => 'customer', 'items.*.product_id' => 'product', 'items.*.quantity' => 'quantity', 'items.*.unit_price' => 'unit price'];
    }

    public function messages()
    {
        return ['items.required' => 'Add at least one product.', 'items.min' => 'Add at least one product.'];
    }

    protected function references(): array
    {
        return [
            'customer_id' => \App\Models\Customer::class,
            'items.*.product_id' => \App\Models\Product::class,
        ];
    }
}
