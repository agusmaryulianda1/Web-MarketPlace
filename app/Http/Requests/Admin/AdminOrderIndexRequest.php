<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Order::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
            'order_status' => ['nullable', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled', 'partially_cancelled'])],
            'payment_method' => ['nullable', Rule::in(['bank_transfer', 'cod'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
