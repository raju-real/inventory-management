<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9._\-\/]+$/', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:60', 'regex:/^[\x21-\x7E]+$/', Rule::unique('products', 'barcode')->ignore($product)],
            'category_id' => ['required', Rule::exists('categories', 'unique_id')],
            'unit_id' => ['required', Rule::exists('units', 'unique_id')],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'sales_price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'min_stock' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'opening_stock' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function attributes()
    {
        return ['sku' => 'product code', 'category_id' => 'category', 'unit_id' => 'unit', 'min_stock' => 'minimum stock'];
    }

    public function messages()
    {
        return ['barcode.regex' => 'The barcode cannot contain spaces or special characters.'];
    }

    protected function references(): array
    {
        return [
            'category_id' => \App\Models\Category::class,
            'unit_id' => \App\Models\Unit::class,
        ];
    }
}
