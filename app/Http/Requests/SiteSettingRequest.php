<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'site_name' => ['required', 'string', 'max:100'],
            'site_email' => ['nullable', 'email:rfc,filter', 'max:255'],
            'site_phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'site_address' => ['nullable', 'string', 'max:255'],
            'site_website' => ['nullable', 'url', 'max:255'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg', 'max:512'],
        ];
    }

    public function messages()
    {
        return ['site_phone.regex' => 'Enter a valid phone number (6-20 digits).'];
    }
}
