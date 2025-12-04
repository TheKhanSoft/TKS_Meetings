<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $notificationId = $this->route('notification') ? $this->route('notification')->id : null;

        return [
            'minute_id' => ['required', 'exists:minutes,id'],
            'notification_no' => ['required', 'string', 'max:255', Rule::unique('notifications')->ignore($notificationId)],
            'notification_date' => ['required', 'date'],
        ];
    }
}
