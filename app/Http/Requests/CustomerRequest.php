<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Walk-in Customer is the one customer without a mobile number.
            'mobile' => [Rule::requiredIf(! $this->route('customer')?->is_walk_in), 'nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'email' => ['nullable', 'email:rfc,filter', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999999', 'max:999999999999'],
            'status' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return ['mobile.regex' => 'Enter a valid mobile number (6-20 digits).'];
    }
}
