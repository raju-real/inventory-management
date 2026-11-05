<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SalesReturnRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'sale_id' => ['required', Rule::exists('sales', 'unique_id')],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_detail_id' => ['required', 'string', 'size:10'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.condition' => ['required', 'in:good,damaged'],
        ];
    }

    public function attributes()
    {
        return ['items.*.quantity' => 'return quantity', 'items.*.condition' => 'condition'];
    }

    protected function references(): array
    {
        return [
            'sale_id' => \App\Models\Sale::class,
            'items.*.sale_detail_id' => \App\Models\SaleDetail::class,
        ];
    }
}
