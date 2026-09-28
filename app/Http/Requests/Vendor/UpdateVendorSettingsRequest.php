<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'vendor';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}