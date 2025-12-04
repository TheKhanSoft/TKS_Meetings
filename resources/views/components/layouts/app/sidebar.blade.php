<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    
    {{-- CSS to hide scrollbar but keep functionality for a sleek look --}}
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    <x-mary-toast />

    @if(\App\Models\Setting::get('maintenance_mode') && auth()->user() && (auth()->user()->hasRole(['Super Admin', 'VC']) || auth()->user()->can('bypass maintenance')))
        <div class="bg-error text-error-content text-center py-2 font-bold text-sm sticky top-0 z-50 shadow-md">
            <div class="flex items-center justify-center gap-2">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <span>MAINTENANCE MODE IS ACTIVE - REGULAR USERS CANNOT ACCESS THE SITE</span>
            </div>
        </div>
    @endif

    {{-- MOBILE HEADER (Keep as is, hidden on desktop) --}}
    <x-mary-nav sticky class="lg:hidden bg-base-100 border-b border-base-content/10">
        <x-slot:brand>
            <label for="main-drawer" class="mr-3 lg:hidden">
                <x-mary-icon name="o-bars-3" class="cursor-pointer" />
            </label>
            <span class="font-bold"> {{ \App\Models\Setting::get('site_name', config('app.name')) }}</span>
        </x-slot:brand>
    </x-mary-nav>

    <x-mary-main drawer="main-drawer" collapsible full-width>

        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" class="bg-base-100 border-r border-base-content/10 w-72 relative">
            
            {{-- Toggle Button (Absolute Positioned outside scrollbar). Kept outside scrollable area and offset to avoid overlay scrollbar. --}}
            <button @click="toggle" class="absolute top-6 z-50 btn btn-circle btn-xs btn-primary shadow-md hidden lg:flex" style="right: -22px;" title="Toggle Sidebar">
                <x-mary-icon name="o-chevron-left" class="w-3 h-3 transition-transform duration-300" ::class="collapsed ? 'rotate-180' : ''" />
            </button>

            {{-- Flex container to push User Profile to bottom --}}
            <div class="flex flex-col h-full">

                {{-- 1. BRAND & SEARCH --}}
                <div class="p-5 pb-0 shrink-0">
                    
                    {{-- Expanded Header --}}
                    <div class="flex items-center gap-2 mb-6 mary-hideable">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                            @if($logo = \App\Models\Setting::get('logo_url'))
                                <img src="{{ $logo }}" class="w-8 h-8 object-contain" />
                            @else
                                <x-app-logo class="w-8 h-8 text-primary" />
                            @endif
                            <span class="font-bold text-lg tracking-tight">
                                {{ \App\Models\Setting::get('site_name', config('app.name')) }}
                            </span>
                        </a>
                    </div>

                    {{-- Collapsed Header --}}
                    <div class="display-when-collapsed hidden text-center mb-6">
                        <a href="{{ route('dashboard') }}" wire:navigate>
                            @if($logo = \App\Models\Setting::get('logo_url'))
                                <img src="{{ $logo }}" class="w-6 h-6 object-contain mx-auto" />
                            @else
                                <x-app-logo class="w-6 h-6 text-primary mx-auto" />
                            @endif
                        </a>
                    </div>

                    {{-- Search Trigger --}}
                    <div class="mary-hideable">
                        <button class="btn btn-sm w-full justify-between bg-base-200 border-0 font-normal text-base-content/60 hover:bg-base-300 transition-colors" @click.stop="$dispatch('mary-search-open')">
                            <div class="flex items-center gap-2">
                                <x-mary-icon name="o-magnifying-glass" class="w-4 h-4" />
                                <span>Search...</span>
                            </div>
                            <kbd class="kbd kbd-xs bg-base-100">Ctrl+G</kbd>
                        </button>
                    </div>
                </div>

                {{-- 2. SCROLLABLE MENU AREA --}}
                <div class="flex-1 overflow-y-auto no-scrollbar py-6 px-3">
                    <x-mary-menu activate-by-route>

                        {{-- Section: Platform --}}
                        {{-- UX Tip: No icon for section headers. Small, uppercase, muted text. --}}
                        @can('view dashboard')
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-2 mb-2 mary-hideable">
                                Platform
                            </div>
                            
                            <x-mary-menu-item route="dashboard" icon="o-home" title="Dashboard" />
                        @endcan

                        {{-- Section: Meetings --}}
                        @if(auth()->user()->canAny(['view meetings', 'view agenda items', 'view minutes', 'view notifications', 'view announcements']))
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-8 mb-2 mary-hideable">
                                Meeting Management
                            </div>

                            @can('view meetings')
                                <x-mary-menu-item route="meetings.index" icon="o-calendar-days" title="Meetings" />
                            @endcan
                            @can('view agenda items')
                                <x-mary-menu-item route="agenda-items.index" icon="o-document-text" title="Agendas" />
                            @endcan
                            @can('view minutes')
                                <x-mary-menu-item route="minutes.index" icon="o-clock" title="Minutes" />
                            @endcan
                            @can('view participants')
                                <x-mary-menu-item route="participants.index" icon="o-user-group" title="Participants" />
                            @endcan
                            @can('view notifications')
                                <x-mary-menu-item route="notifications.index" icon="o-bell" title="Notifications" />
                            @endcan

                            @if(\App\Models\Setting::get('enable_announcements', true))
                                @can('view announcements')
                                    <x-mary-menu-item route="announcements.index" icon="o-megaphone" title="Announcements">
                                        <x-slot:badge>
                                            <div class="badge badge-xs badge-error"></div>
                                        </x-slot:badge>
                                    </x-mary-menu-item>
                                @endcan
                            @endif
                        @endif

                        {{-- Section: Admin --}}
                        @if(auth()->user()->canAny(['view users', 'view roles', 'view permissions', 'view positions', 'view employment statuses', 'view settings', 'view meeting types', 'view agenda item types', 'view help categories', 'view help articles']))
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-8 mb-2 mary-hideable">
                                Administration
                            </div>

                            @if(auth()->user()->canAny(['view users', 'view roles', 'view permissions', 'view positions', 'view employment statuses']))
                                <x-mary-menu-sub title="User Management" icon="o-users">
                                    @can('view users')
                                        <x-mary-menu-item route="users.index" title="Users" icon="o-user" />
                                    @endcan
                                    @can('view roles')
                                        <x-mary-menu-item route="roles.index" title="Roles" icon="o-shield-check" />
                                    @endcan
                                    @can('view permissions')
                                        <x-mary-menu-item route="permissions.index" title="Permissions" icon="o-key" />
                                    @endcan
                                    @can('view positions')
                                        <x-mary-menu-item route="positions.index" title="Positions" icon="o-briefcase" />
                                    @endcan
                                    @can('view employment statuses')
                                        <x-mary-menu-item route="employment-statuses.index" title="Statuses" icon="o-check-badge" />
                                    @endcan
                                </x-mary-menu-sub>
                            @endif

                            @if(auth()->user()->canAny(['view settings', 'view meeting types', 'view agenda item types', 'view help categories', 'view help articles']))
                                <x-mary-menu-sub title="Settings" icon="o-cog-6-tooth">
                                    @can('view settings')
                                        <x-mary-menu-item route="settings.system" title="System" icon="o-adjustments-horizontal" />
                                    @endcan
                                    @can('view meeting types')
                                        <x-mary-menu-item route="meeting-types.index" title="Meeting Types" icon="o-tag" />
                                    @endcan
                                    @can('view agenda item types')
                                        <x-mary-menu-item route="agenda-item-types.index" title="Agenda Types" icon="o-bookmark" />
                                    @endcan
                                    @can('view settings')
                                        <x-mary-menu-item route="settings.keywords" title="Keywords" icon="o-hashtag" />
                                    @endcan
                                    @if(auth()->user()->can('view help categories') || auth()->user()->can('view help articles'))
                                        <x-mary-menu-item route="help.admin.index" title="Help Center" icon="o-lifebuoy" />
                                    @endif
                                </x-mary-menu-sub>
                            @endif
                        @endif

                        <x-mary-menu-separator />
                        
                        @php
                            $helpSetting = \App\Models\Setting::get('help_url');
                            $isUrl = $helpSetting && (str_starts_with($helpSetting, 'http://') || str_starts_with($helpSetting, 'https://'));
                        @endphp

                        @if($helpSetting)
                            @if($isUrl)
                                <x-mary-menu-item link="{{ $helpSetting }}" icon="o-question-mark-circle" title="Help & Support" external />
                            @elseif(\Illuminate\Support\Facades\Route::has($helpSetting))
                                <x-mary-menu-item route="{{ $helpSetting }}" icon="o-question-mark-circle" title="Help & Support" />
                            @else
                                <x-mary-menu-item link="#" icon="o-exclamation-triangle" title="Fix Help Link" class="text-warning" tooltip="Invalid Route: {{ $helpSetting }}" />
                            @endif
                        @elseif(\Illuminate\Support\Facades\Route::has('help.index'))
                            <x-mary-menu-item route="help.index" icon="o-question-mark-circle" title="Help & Support" />
                        @endif

                    </x-mary-menu>
                </div>

                {{-- 3. USER PROFILE (STICKY BOTTOM) --}}
                @if($user = auth()->user())
                    <div class="p-3 border-t border-base-content/10 bg-base-100 shrink-0">
                        <x-mary-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="rounded-lg hover:bg-base-200 transition-colors cursor-pointer group" link="{{ route('profile.edit') }}">
                            <x-slot:actions>
                                @if(\App\Models\Setting::get('enable_theme_toggle', true))
                                    <x-mary-theme-toggle class="btn-ghost btn-xs" />
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="hidden" id="logout-form">
                                    @csrf
                                </form>
                                <x-mary-button icon="o-power" class="btn-circle btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity" tooltip-left="Logoff" no-wire-navigate onclick="document.getElementById('logout-form').submit()" />
                            </x-slot:actions>
                        </x-mary-list-item>
                    </div>
                @endif            
            </div>
        </x-slot:sidebar>

        {{-- MAIN CONTENT --}}
        <x-slot:content class="px-5 py-8 lg:px-10 lg:py-10">
            <div class="max-w-screen-2xl mx-auto">
                {{ $slot }}
            </div>

            @if($footerText = \App\Models\Setting::get('footer_text'))
                <div class="mt-10 border-t border-base-content/10 pt-6 text-center text-sm text-base-content/50">
                    {{ $footerText }}
                </div>
            @endif
        </x-slot:content>
    </x-mary-main>

    <x-mary-spotlight />
</body>
</html>