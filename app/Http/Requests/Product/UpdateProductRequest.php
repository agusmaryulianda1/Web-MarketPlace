<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where('status', 'active')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}