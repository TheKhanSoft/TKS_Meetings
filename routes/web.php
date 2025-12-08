<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/system', 'settings.system')->name('settings.system');
    Volt::route('settings/keywords', 'settings.keywords')->name('settings.keywords');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('roles', 'roles.index')->name('roles.index');
    Volt::route('permissions', 'permissions.index')->name('permissions.index');
    Volt::route('users/{user}/positions', 'users.positions')->name('users.positions');
    Volt::route('positions', 'positions.index')->name('positions.index');
    Volt::route('meeting-types', 'meeting-types.index')->name('meeting-types.index');
    Volt::route('meeting-types/{meetingType}/permissions', 'meeting-types.permissions')->name('meeting-types.permissions');
    Volt::route('meetings', 'meetings.index')->name('meetings.index');
    Volt::route('agenda-item-types', 'agenda-item-types.index')->name('agenda-item-types.index');
    Volt::route('agenda-items', 'agenda-items.index')->name('agenda-items.index');
    Volt::route('minutes', 'minutes.index')->name('minutes.index');
    Volt::route('participants', 'participants.index')->name('participants.index');
    Volt::route('notifications', 'notifications.index')->name('notifications.index');
    Volt::route('announcements', 'announcements.index')->name('announcements.index');
    Volt::route('employment-statuses', 'employment-statuses.index')->name('employment-statuses.index');

    // Help Center
    Volt::route('help', 'help.index')->name('help.index');
    Volt::route('help/{article:slug}', 'help.article')->name('help.article');
    Volt::route('admin/help', 'help.admin.index')->name('help.admin.index');

    Route::get('meetings/{id}/minutes/download/{format?}', [App\Http\Controllers\MeetingController::class, 'downloadMinutes'])->name('meetings.minutes.download');
    Route::get('meetings/{id}/minutes/view', [App\Http\Controllers\MeetingController::class, 'viewMinutes'])->name('meetings.minutes.view');
    Route::get('meetings/{id}/agenda/download/{format?}', [App\Http\Controllers\MeetingController::class, 'downloadAgenda'])->name('meetings.agenda.download');
    Route::get('meetings/{id}/agenda/view', [App\Http\Controllers\MeetingController::class, 'viewAgenda'])->name('meetings.agenda.view');

});
