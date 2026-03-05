<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTradieRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'unique:tradies,email'],
            'password'       => ['required', 'string', 'min:8', 'confirmed'],
            'personal_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],  // E.164 format
            'skills'         => ['sometimes', 'array'],
            'skills.*'       => ['string', 'max:100'],
            'timezone'       => ['sometimes', 'string', 'timezone'],
            'area_code'      => ['sometimes', 'string', 'max:10'],
            'tenant_id'      => ['required', 'uuid', 'exists:tenants,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_phone.regex' => 'Phone number must be in E.164 format, e.g. +61412345678',
        ];
    }
}
