<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendorProductIndexRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('viewAny', \App\Models\Product::class) ?? false; }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'out_of_stock'])],
        ];
    }

    public function effectiveSearch(): ?string
    {
        $search = trim((string) $this->validated('search'));

        return Str::length($search) >= 2 ? $search : null;
    }
}