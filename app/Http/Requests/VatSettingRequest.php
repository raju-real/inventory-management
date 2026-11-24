<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VatSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'vat_default_mode' => ['required', 'in:product,order'],
            // Only sent (and needed) when VAT is on the total order.
            'vat_default_type' => ['required_if:vat_default_mode,order', 'nullable', 'in:percentage,flat'],
            'vat_default_value' => ['required_if:vat_default_mode,order', 'nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('vat_default_mode') === 'order' && $this->input('vat_default_type') === 'percentage' && (float) $this->input('vat_default_value') > 100) {
                $validator->errors()->add('vat_default_value', 'VAT cannot be more than 100%.');
            }
        });
    }

    public function attributes()
    {
        return ['vat_default_mode' => 'VAT calculation', 'vat_default_type' => 'VAT type', 'vat_default_value' => 'VAT'];
    }

    public function messages()
    {
        return ['vat_default_value.required_if' => 'Enter the VAT for the total order.', 'vat_default_type.required_if' => 'Choose the VAT type.'];
    }
}
