<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class AssignJobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tradie_id' => ['required', 'uuid', 'exists:tradies,id'],
        ];
    }
}
