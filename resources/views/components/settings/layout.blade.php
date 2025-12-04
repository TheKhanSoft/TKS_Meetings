<div class="flex flex-col gap-6 md:flex-row">
    <div class="w-full pb-4 md:w-56">
        <x-mary-menu class="rounded-box border border-base-content/10 bg-base-100" activate-by-route>
            <x-mary-menu-item route="profile.edit" icon="o-user-circle">{{ __('Profile') }}</x-mary-menu-item>
            <x-mary-menu-item route="user-password.edit" icon="o-key">{{ __('Password') }}</x-mary-menu-item>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <x-mary-menu-item route="two-factor.show" icon="o-shield-check">{{ __('Two-Factor Auth') }}</x-mary-menu-item>
            @endif
            <x-mary-menu-item route="appearance.edit" icon="o-adjustments-horizontal">{{ __('Appearance') }}</x-mary-menu-item>
        </x-mary-menu>
    </div>

    <div class="flex-1 self-stretch">
        <x-mary-header
            :title="$heading ?? ''"
            :subtitle="$subheading ?? ''"
            class="mb-5"
        />

        <div class="w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
