<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ChangeProductStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('product')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:active,inactive']];
    }
}