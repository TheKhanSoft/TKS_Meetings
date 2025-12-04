<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $meetingTypeId = $this->route('meeting_type') ? $this->route('meeting_type')->id : null;
        // If using Volt, we might not have route parameter binding in the same way, 
        // but let's assume standard usage or manual validation in Volt.
        // If called from Controller, this works.
        // If called from Volt manually, we might need to pass ID differently.
        // But for the Request class itself:

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('meeting_types')->ignore($meetingTypeId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
