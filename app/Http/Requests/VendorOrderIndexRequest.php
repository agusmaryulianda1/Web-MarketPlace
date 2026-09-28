<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Order::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
            'order_status' => ['nullable', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled'])],
        ];
    }
}
