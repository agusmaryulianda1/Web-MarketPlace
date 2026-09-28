<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'buyer'; }

    public function rules(): array { return ['quantity' => ['required', 'integer', 'min:1']]; }
}