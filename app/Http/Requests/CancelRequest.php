<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return ['cancel_reason' => ['required', 'string', 'max:255']];
    }

    public function attributes()
    {
        return ['cancel_reason' => 'cancellation reason'];
    }
}
