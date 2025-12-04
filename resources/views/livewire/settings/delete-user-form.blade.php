<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $password = '';
    public bool $confirmingDeletion = false;

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->confirmingDeletion = false;
        $this->redirect('/', navigate: true);
    }

    public function confirmUserDeletion(): void
    {
        $this->resetErrorBag();
        $this->reset('password');
        $this->confirmingDeletion = true;
    }
}; ?>

<section class="mt-10 space-y-6">
    <x-mary-header
        :title="__('Delete account')"
        :subtitle="__('Delete your account and all of its resources')"
        class="mb-5"
    />

    <x-mary-button
        class="btn-error"
        data-test="delete-user-button"
        wire:click="confirmUserDeletion"
    >
        {{ __('Delete account') }}
    </x-mary-button>

    <x-mary-modal wire:model="confirmingDeletion" class="max-w-lg" :title="__('Are you sure you want to delete your account?')" :subtitle="__('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.')" separator>
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <x-mary-input wire:model="password" :label="__('Password')" type="password" />

            <x-slot:actions>
                <x-mary-button type="button" class="btn-ghost" wire:click="$set('confirmingDeletion', false)">
                    {{ __('Cancel') }}
                </x-mary-button>

                <x-mary-button type="submit" class="btn-error" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </x-mary-button>
            </x-slot:actions>
        </form>
    </x-mary-modal>
</section>
