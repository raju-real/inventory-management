<?php

namespace App\Http\Requests;

use App\Models\BankAccount;
use App\Models\ExpenseCategory;
use Illuminate\Validation\Rule;

class ExpenseRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'category_id' => ['required', Rule::exists('expense_categories', 'unique_id')],
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'unique_id')],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes()
    {
        return ['entry_date' => 'date', 'category_id' => 'category', 'bank_account_id' => 'bank account'];
    }

    protected function references(): array
    {
        return ['category_id' => ExpenseCategory::class, 'bank_account_id' => BankAccount::class];
    }
}
