<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgendaItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meeting_id' => ['required', 'exists:meetings,id'],
            'agenda_item_type_id' => ['required', 'exists:agenda_item_types,id'],
            'sequence_number' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'discussion_status' => ['required', 'string', Rule::in(['pending', 'discussed', 'deferred', 'approved', 'rejected', 'deferred', 'withdrawn'])],
            'is_left_over' => ['boolean'],
        ];
    }
}
