<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:255'],
            'meeting_type_id' => ['required', 'exists:meeting_types,id'],
            'date' => ['required', 'date'],
            'time' => ['required'], // Time validation can be tricky, usually string or date_format:H:i
            'is_last' => ['boolean'],
            'director_id' => ['nullable', 'exists:users,id'],
            'registrar_id' => ['nullable', 'exists:users,id'],
            'vc_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
