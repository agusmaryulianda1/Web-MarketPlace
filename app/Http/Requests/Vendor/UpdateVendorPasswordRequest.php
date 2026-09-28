<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateVendorPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'vendor';
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'different:current_password', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}