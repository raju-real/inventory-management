<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BarcodeSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'barcode_enabled' => ['required', 'boolean'],
            'barcode_min_length' => ['required', 'integer', 'min:1', 'max:30'],
            'barcode_max_interval' => ['required', 'integer', 'min:10', 'max:300'],
            'barcode_suffix' => ['required', 'in:enter,tab,none'],
            'barcode_beep' => ['required', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'barcode_min_length' => 'minimum length',
            'barcode_max_interval' => 'key interval',
            'barcode_suffix' => 'suffix key',
        ];
    }
}
