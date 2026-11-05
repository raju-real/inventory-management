<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PurchaseReturnRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'purchase_id' => ['required', Rule::exists('purchases', 'unique_id')],
            'return_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_detail_id' => ['required', 'string', 'size:10'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes()
    {
        return ['items.*.quantity' => 'return quantity'];
    }

    protected function references(): array
    {
        return [
            'purchase_id' => \App\Models\Purchase::class,
            'items.*.purchase_detail_id' => \App\Models\PurchaseDetail::class,
        ];
    }
}
