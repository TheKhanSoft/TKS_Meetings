<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function getAllNotifications()
    {
        return Notification::with(['minute'])->latest()->get();
    }

    public function createNotification(array $data): Notification
    {
        return Notification::create($data);
    }

    public function updateNotification(Notification $notification, array $data): Notification
    {
        $notification->update($data);
        return $notification;
    }

    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }
}
