<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CustomerPaymentRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => ['required', Rule::exists('customers', 'unique_id')],
            'sale_id' => ['nullable', Rule::exists('sales', 'unique_id')],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'method' => ['required', 'in:cash,bank,card,mobile,other'],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes()
    {
        return ['customer_id' => 'customer', 'method' => 'payment method'];
    }

    protected function references(): array
    {
        return [
            'customer_id' => \App\Models\Customer::class,
            'sale_id' => \App\Models\Sale::class,
        ];
    }
}
