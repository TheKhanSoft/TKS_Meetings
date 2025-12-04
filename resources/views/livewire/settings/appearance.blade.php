<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div
            x-data="appearancePicker()"
            x-init="init()"
            class="flex flex-wrap gap-3"
        >
            <button type="button" class="btn min-w-[140px] justify-center gap-2" :class="buttonClasses('light')" @click="apply('light')">
                <x-mary-icon name="o-sun" />
                <span>{{ __('Light') }}</span>
            </button>

            <button type="button" class="btn min-w-[140px] justify-center gap-2" :class="buttonClasses('dark')" @click="apply('dark')">
                <x-mary-icon name="o-moon" />
                <span>{{ __('Dark') }}</span>
            </button>

            <button type="button" class="btn min-w-[140px] justify-center gap-2" :class="buttonClasses('system')" @click="apply('system')">
                <x-mary-icon name="o-computer-desktop" />
                <span>{{ __('System') }}</span>
            </button>
        </div>
    </x-settings.layout>
</section>

@once
    <script>
        window.appearancePicker = function () {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            return {
                preference: localStorage.getItem('mary-theme-preference')?.replaceAll('"', '') || 'system',
                init() {
                    this.apply(this.preference, false);
                    mediaQuery.addEventListener('change', () => {
                        if (this.preference === 'system') {
                            this.apply('system', false);
                        }
                    });
                },
                buttonClasses(value) {
                    return this.preference === value ? 'btn-primary text-white' : 'btn-outline';
                },
                apply(value, persist = true) {
                    this.preference = value;

                    if (persist) {
                        localStorage.setItem('mary-theme-preference', value);
                    }

                    let theme = value;
                    if (value === 'system') {
                        theme = mediaQuery.matches ? 'dark' : 'light';
                    }

                    const themeClass = theme;

                    localStorage.setItem('mary-theme', JSON.stringify(theme));
                    localStorage.setItem('mary-class', JSON.stringify(themeClass));

                    document.documentElement.setAttribute('data-theme', theme);
                    document.documentElement.setAttribute('class', themeClass);

                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
                }
            };
        };
    </script>
@endonce
