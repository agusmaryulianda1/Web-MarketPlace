<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'buyer'; }

    public function rules(): array
    {
        return ['product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'integer', 'min:1']];
    }
}