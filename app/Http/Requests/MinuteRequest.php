<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agenda_item_id' => ['required', 'exists:agenda_items,id'],
            'decision' => ['required', 'string'],
            'action_required' => ['nullable', 'string'],
            'approval_status' => ['required', 'string', Rule::in(['draft', 'approved'])],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'target_due_date' => ['nullable', 'date'],
        ];
    }
}
