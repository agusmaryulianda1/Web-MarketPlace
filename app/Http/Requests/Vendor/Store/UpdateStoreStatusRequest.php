<?php

namespace App\Http\Requests\Vendor\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->user()?->vendor?->store;

        return $store !== null && $this->user()->can('update', $store);
    }

    public function rules(): array
    {
        return ['is_open' => ['required', 'boolean']];
    }
}