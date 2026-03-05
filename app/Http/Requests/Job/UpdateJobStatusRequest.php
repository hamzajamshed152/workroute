<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'              => ['required', 'string', 'in:in_progress,completed,cancelled'],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }
}
