<?php

namespace App\Http\Requests\Tradie;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'is_available' => ['required', 'boolean'],
        ];
    }
}
