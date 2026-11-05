<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9\-\s]+$/', Rule::unique('bank_accounts', 'account_number')->ignore($this->route('bank_account'))],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function attributes()
    {
        return ['opening_balance' => 'current balance'];
    }

    public function messages()
    {
        return ['account_number.regex' => 'The account number can only contain letters, numbers, spaces and dashes.'];
    }
}
