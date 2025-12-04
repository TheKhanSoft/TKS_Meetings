<?php

use Livewire\Volt\Component;
use App\Models\Setting;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public $site_name;
    public $organization_name;
    public $contact_email;
    public $timezone;
    public $maintenance_mode = false;
    public $primary_color;
    public $logo_url;
    public $default_locale;
    public $date_format;
    public $time_format;
    public $pagination_size = 15;
    public $default_theme;
    public $enable_theme_toggle = true;
    public $allow_registration = true;
    public $footer_text;
    public $enable_announcements = true;
    public $help_url;
    public $require_2fa = false;
    public $max_upload_size = 10240;
    public $allowed_file_types = 'pdf,doc,docx,xls,xlsx,jpg,png';
    public $themes = [];

    public function mount()
    {
        if (!auth()->user()->can('view settings')) {
            $this->error('Unauthorized access. Redirecting to dashboard...');
            return $this->redirect(route('dashboard'), navigate: true);
        }
        $this->site_name = Setting::get('site_name', config('app.name'));
        $this->organization_name = Setting::get('organization_name', 'TheKhanSoft');
        $this->contact_email = Setting::get('contact_email', '');
        $this->timezone = Setting::get('timezone', config('app.timezone'));
        $this->maintenance_mode = (bool) Setting::get('maintenance_mode', false);
        $this->primary_color = Setting::get('primary_color', '#4f46e5');
        $this->logo_url = Setting::get('logo_url', '');
        $this->default_locale = Setting::get('default_locale', app()->getLocale());
        $this->date_format = Setting::get('date_format', 'M d, Y');
        $this->time_format = Setting::get('time_format', 'H:i');
        $this->pagination_size = Setting::get('pagination_size', 15);
        $this->default_theme = Setting::get('default_theme', 'system');
        $this->enable_theme_toggle = (bool) Setting::get('enable_theme_toggle', true);
        $this->allow_registration = (bool) Setting::get('allow_registration', true);
        $this->footer_text = Setting::get('footer_text', '© ' . date('Y') . ' TheKhanSoft. All rights reserved.');
        $this->enable_announcements = (bool) Setting::get('enable_announcements', true);
        $this->help_url = Setting::get('help_url', '');
        $this->require_2fa = (bool) Setting::get('require_2fa', false);
        $this->max_upload_size = Setting::get('max_upload_size', 10240);
        $this->allowed_file_types = Setting::get('allowed_file_types', 'pdf,doc,docx,xls,xlsx,jpg,png');
        
        $this->themes = [
            ['id' => 'system', 'name' => 'System Default'],
            ['id' => 'light', 'name' => 'Light'],
            ['id' => 'dark', 'name' => 'Dark'],
            ['id' => 'cupcake', 'name' => 'Cupcake'],
            ['id' => 'bumblebee', 'name' => 'Bumblebee'],
            ['id' => 'emerald', 'name' => 'Emerald'],
            ['id' => 'corporate', 'name' => 'Corporate'],
            ['id' => 'synthwave', 'name' => 'Synthwave'],
            ['id' => 'retro', 'name' => 'Retro'],
            ['id' => 'cyberpunk', 'name' => 'Cyberpunk'],
            ['id' => 'valentine', 'name' => 'Valentine'],
            ['id' => 'halloween', 'name' => 'Halloween'],
            ['id' => 'garden', 'name' => 'Garden'],
            ['id' => 'forest', 'name' => 'Forest'],
            ['id' => 'aqua', 'name' => 'Aqua'],
            ['id' => 'lofi', 'name' => 'Lofi'],
            ['id' => 'pastel', 'name' => 'Pastel'],
            ['id' => 'fantasy', 'name' => 'Fantasy'],
            ['id' => 'wireframe', 'name' => 'Wireframe'],
            ['id' => 'black', 'name' => 'Black'],
            ['id' => 'luxury', 'name' => 'Luxury'],
            ['id' => 'dracula', 'name' => 'Dracula'],
            ['id' => 'cmyk', 'name' => 'CMYK'],
            ['id' => 'autumn', 'name' => 'Autumn'],
            ['id' => 'business', 'name' => 'Business'],
            ['id' => 'acid', 'name' => 'Acid'],
            ['id' => 'lemonade', 'name' => 'Lemonade'],
            ['id' => 'night', 'name' => 'Night'],
            ['id' => 'coffee', 'name' => 'Coffee'],
            ['id' => 'winter', 'name' => 'Winter'],
            ['id' => 'dim', 'name' => 'Dim'],
            ['id' => 'nord', 'name' => 'Nord'],
            ['id' => 'sunset', 'name' => 'Sunset'],
        ];
    }

    public function getLocales()
    {
        return [
            ['id' => 'en', 'name' => 'English (en)'],
            ['id' => 'en_US', 'name' => 'English (US)'],
            ['id' => 'fr', 'name' => 'Français (fr)'],
            ['id' => 'es', 'name' => 'Español (es)'],
        ];
    }

    // getThemes removed as we use property now

    public function getTimezones()
    {
        $identifiers = DateTimeZone::listIdentifiers();
        return array_map(fn($id) => ['id' => $id, 'name' => $id], $identifiers);
    }

    public function save()
    {
        if (!auth()->user()->can('edit settings')) {
            abort(403);
        }
        $this->validate([
            'site_name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'contact_email' => 'nullable|email',
            'timezone' => 'required|string',
            'maintenance_mode' => 'boolean',
            'primary_color' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'default_locale' => 'nullable|string',
            'date_format' => 'nullable|string',
            'time_format' => 'nullable|string',
            'pagination_size' => 'nullable|integer|min:1|max:200',
            'default_theme' => 'required|string',
            'enable_theme_toggle' => 'boolean',
            'allow_registration' => 'boolean',
            'footer_text' => 'nullable|string|max:255',
            'enable_announcements' => 'boolean',
            'help_url' => 'nullable|string',
            'require_2fa' => 'boolean',
            'max_upload_size' => 'required|integer|min:1',
            'allowed_file_types' => 'required|string',
        ]);

        Setting::set('site_name', $this->site_name);
        Setting::set('organization_name', $this->organization_name);
        Setting::set('contact_email', $this->contact_email);
        Setting::set('timezone', $this->timezone);
        Setting::set('maintenance_mode', (int) $this->maintenance_mode);
        Setting::set('primary_color', $this->primary_color);
        Setting::set('logo_url', $this->logo_url);
        Setting::set('default_locale', $this->default_locale);
        Setting::set('date_format', $this->date_format);
        Setting::set('time_format', $this->time_format);
        Setting::set('pagination_size', $this->pagination_size);
        Setting::set('default_theme', $this->default_theme);
        Setting::set('enable_theme_toggle', (int) $this->enable_theme_toggle);
        Setting::set('allow_registration', (int) $this->allow_registration);
        Setting::set('footer_text', $this->footer_text);
        Setting::set('enable_announcements', (int) $this->enable_announcements);
        Setting::set('help_url', $this->help_url);
        Setting::set('require_2fa', (int) $this->require_2fa);
        Setting::set('max_upload_size', $this->max_upload_size);
        Setting::set('allowed_file_types', $this->allowed_file_types);

        $this->success('Settings saved successfully!');

        // Force theme update on client side
        $this->js("
            const theme = '{$this->default_theme}';
            localStorage.setItem('mary-theme', JSON.stringify(theme));
            document.documentElement.setAttribute('data-theme', theme);
        ");
    }
}; ?>

<div>
    <x-mary-header title="System Settings" separator />

    {{-- System Information (Top) --}}
    <div class="mb-8">
        <x-mary-card class="shadow-sm bg-base-200/50 border border-base-content/5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 mb-1">Laravel Version</span>
                    <span class="font-medium font-mono">{{ app()->version() }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 mb-1">PHP Version</span>
                    <span class="font-medium font-mono">{{ phpversion() }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 mb-1">Environment</span>
                    <span class="font-medium">
                        <x-mary-badge :value="app()->environment()" class="badge-neutral badge-sm" />
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 mb-1">Debug Mode</span>
                    <span class="font-medium {{ config('app.debug') ? 'text-warning' : 'text-success' }}">
                        {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
            </div>
        </x-mary-card>
    </div>

    <x-mary-form wire:submit="save">
        <div class="grid lg:grid-cols-3 gap-8">
            
            {{-- Column 1: Branding & Identity --}}
            <div class="space-y-6">
                <div class="flex items-center gap-2 text-lg font-bold text-primary">
                    <x-mary-icon name="o-globe-alt" class="w-6 h-6" /> Branding
                </div>
                <x-mary-card class="shadow-sm border border-base-content/5">
                    <div class="space-y-4">
                        <x-mary-input label="Site Name" wire:model="site_name" />
                        <x-mary-input label="Organization" wire:model="organization_name" />
                        <x-mary-input label="Contact Email" wire:model="contact_email" />
                        <div class="grid grid-cols-2 gap-3">
                            <x-mary-input label="Primary Color" wire:model="primary_color" type="color" class="h-10 w-full cursor-pointer" />
                            <x-mary-input label="Logo URL" wire:model="logo_url" placeholder="https://..." />
                        </div>
                    </div>
                </x-mary-card>
            </div>

            {{-- Column 2: Localization & Display --}}
            <div class="space-y-6">
                <div class="flex items-center gap-2 text-lg font-bold text-primary">
                    <x-mary-icon name="o-language" class="w-6 h-6" /> Localization
                </div>
                <x-mary-card class="shadow-sm border border-base-content/5">
                    <div class="space-y-4">
                        <x-mary-select label="Timezone" wire:model="timezone" :options="$this->getTimezones()" searchable />
                        <div class="grid grid-cols-2 gap-3">
                            <x-mary-select label="Locale" wire:model="default_locale" :options="$this->getLocales()" />
                            <x-mary-select label="Theme" wire:model="default_theme" :options="$themes" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <x-mary-input label="Date Format" wire:model="date_format" placeholder="M d, Y" />
                            <x-mary-input label="Time Format" wire:model="time_format" placeholder="H:i" />
                        </div>
                        <x-mary-input label="Pagination Size" wire:model="pagination_size" type="number" />
                    </div>
                </x-mary-card>
            </div>

            {{-- Column 3: Features & System --}}
            <div class="space-y-6">
                <div class="flex items-center gap-2 text-lg font-bold text-primary">
                    <x-mary-icon name="o-cog-6-tooth" class="w-6 h-6" /> System
                </div>
                <x-mary-card class="shadow-sm border border-base-content/5">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <x-mary-toggle label="Theme Toggle" wire:model="enable_theme_toggle" hint="Allow users to switch themes" right />
                            <x-mary-toggle label="Registration" wire:model="allow_registration" hint="Allow new users to register" right />
                            <x-mary-toggle label="Announcements" wire:model="enable_announcements" hint="Show announcements menu" right />
                            <x-mary-toggle label="Require 2FA" wire:model="require_2fa" hint="Enforce 2FA for all users" right />
                            <x-mary-toggle label="Maintenance Mode" wire:model="maintenance_mode" class="text-error font-bold" right />
                        </div>
                        <hr class="border-base-content/10" />
                        <x-mary-input label="Footer Text" wire:model="footer_text" />
                        <x-mary-input label="Help Link (URL or Route Name)" wire:model="help_url" placeholder="https://... or help.index" />
                        <x-mary-input label="Max Upload Size (KB)" wire:model="max_upload_size" type="number" />
                        <x-mary-input label="Allowed File Types" wire:model="allowed_file_types" hint="Comma separated extensions" />
                    </div>
                </x-mary-card>
            </div>

        </div>

        <div class="flex justify-end mt-8 border-t border-base-content/10 pt-6">
            @can('edit settings')
                <x-mary-button label="Save Changes" class="btn-primary px-8" type="submit" spinner="save" icon="o-check" />
            @endcan
        </div>
    </x-mary-form>
</div>
