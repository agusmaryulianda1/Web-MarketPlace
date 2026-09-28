<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\IndonesiaRegions;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'buyer'; }

    public function rules(): array
    {
        return ['label' => ['nullable', 'string', 'max:100'], 'recipient_name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:30'], 'address' => ['required', 'string'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100', function ($attribute, $value, $fail) { if ($value !== null && ! in_array($value, IndonesiaRegions::provinces(), true)) $fail('Provinsi tidak valid.'); }], 'postal_code' => ['nullable', 'string', 'max:20'], 'is_default' => ['sometimes', 'boolean']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $province = $this->input('province');
            $city = $this->input('city');
            if (($province === null) !== ($city === null)) {
                $validator->errors()->add('city', 'Provinsi dan kota/kabupaten harus dipilih bersama.');
                return;
            }
            if ($province !== null && ! IndonesiaRegions::contains($province, $city)) {
                $validator->errors()->add('city', 'Kota/kabupaten tidak sesuai dengan provinsi.');
            }
        });
    }
}