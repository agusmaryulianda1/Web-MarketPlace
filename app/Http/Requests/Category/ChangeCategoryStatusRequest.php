<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCategoryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('category')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:active,inactive']];
    }
}