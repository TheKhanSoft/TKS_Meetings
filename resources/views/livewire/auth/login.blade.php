<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-mary-input
                    name="email"
                    :label="__('Email address')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="w-full"
                />
                @error('email')
                    <div class="text-red-500 text-sm mt-1 flex items-center gap-1">
                        <x-mary-icon name="o-exclamation-circle" class="w-4 h-4" />
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Password -->
            <div class="relative">
                <x-mary-input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    class="w-full"
                />
                @error('password')
                    <div class="text-red-500 text-sm mt-1 flex items-center gap-1">
                        <x-mary-icon name="o-exclamation-circle" class="w-4 h-4" />
                        {{ $message }}
                    </div>
                @enderror

                @if (Route::has('password.request'))
                    <a class="absolute top-0 text-sm end-0 link" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <x-mary-checkbox name="remember" :label="__('Remember me')" @checked(old('remember')) />

            <div class="flex items-center justify-end">
                <x-mary-button type="submit" class="btn-primary w-full" data-test="login-button">
                    {{ __('Log in') }}
                </x-mary-button>
            </div>
        </form>


        @if (Route::has('register') && \App\Models\Setting::get('allow_registration', true))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Dont have an account?') }}</span>
                <a href="{{ route('register') }}" class="link" wire:navigate>{{ __('Sign up') }}</a>
            </div>
        @endif
    </div>
</x-layouts.auth>
