<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        return view('livewire.notifications.index');
    }

    public function store(NotificationRequest $request)
    {
        $this->notificationService->createNotification($request->validated());
        return redirect()->route('notifications.index')->with('success', 'Notification created successfully.');
    }

    public function update(NotificationRequest $request, Notification $notification)
    {
        $this->notificationService->updateNotification($notification, $request->validated());
        return redirect()->route('notifications.index')->with('success', 'Notification updated successfully.');
    }

    public function destroy(Notification $notification)
    {
        $this->notificationService->deleteNotification($notification);
        return redirect()->route('notifications.index')->with('success', 'Notification deleted successfully.');
    }
}
