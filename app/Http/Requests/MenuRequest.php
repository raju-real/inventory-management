<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class MenuRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('menus', 'unique_id')->whereNull('route')],
            'icon' => ['required', 'string', 'max:60', 'regex:/^bi-[a-z0-9\-]+$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages()
    {
        return ['icon.regex' => 'Use a Bootstrap Icons class name, for example bi-box.'];
    }

    protected function references(): array
    {
        return [
            'parent_id' => \App\Models\Menu::class,
        ];
    }
}
