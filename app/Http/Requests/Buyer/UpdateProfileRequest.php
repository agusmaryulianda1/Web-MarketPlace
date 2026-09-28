<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'buyer'; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}