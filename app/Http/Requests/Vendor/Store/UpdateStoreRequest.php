<?php

namespace App\Http\Requests\Vendor\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->user()?->vendor?->store;

        return $store !== null && $this->user()->can('update', $store);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('stores', 'slug')->ignore($this->user()?->vendor?->store?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}