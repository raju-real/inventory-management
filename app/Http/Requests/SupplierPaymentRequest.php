<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SupplierPaymentRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'unique_id')],
            'purchase_id' => ['nullable', Rule::exists('purchases', 'unique_id')],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'method' => ['required', 'in:cash,bank,card,mobile,other'],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes()
    {
        return ['supplier_id' => 'supplier', 'method' => 'payment method'];
    }

    protected function references(): array
    {
        return [
            'supplier_id' => \App\Models\Supplier::class,
            'purchase_id' => \App\Models\Purchase::class,
        ];
    }
}
