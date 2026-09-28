<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderPaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updatePaymentStatus', $this->route('order')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['paid', 'failed'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
