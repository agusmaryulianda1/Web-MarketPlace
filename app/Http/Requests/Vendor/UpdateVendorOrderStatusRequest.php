<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'vendor';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled'])],
            'cancellation_reason' => ['nullable', 'string', 'required_if:status,cancelled', 'min:5', 'max:1000'],
        ];
    }
}