<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'buyer';
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,cod'],
        ];
    }
}