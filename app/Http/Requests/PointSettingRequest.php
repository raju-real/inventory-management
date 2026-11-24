<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PointSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'points_earn_enabled' => ['required', 'boolean'],
            'points_earn_amount' => ['required', 'numeric', 'min:1', 'max:99999999'],
            'points_redeem_enabled' => ['required', 'boolean'],
            'points_value' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ];
    }

    public function attributes()
    {
        return ['points_earn_amount' => 'amount for 1 point', 'points_value' => 'value of 1 point'];
    }
}
