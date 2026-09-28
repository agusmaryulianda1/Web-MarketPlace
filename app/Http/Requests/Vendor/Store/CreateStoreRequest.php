<?php

namespace App\Http\Requests\Vendor\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'vendor';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', new Unique('stores', 'slug')],
            'description' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}