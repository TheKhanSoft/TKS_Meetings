<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        /* 1. Hide Scrollbar but keep functionality */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* 2. Prevent Alpine flicker */
        [x-cloak] { display: none !important; }

        /* 3. CRITICAL FIX: Hide default DaisyUI/Browser details marker (Double Arrow Fix) */
        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        details > summary::after { display: none !important; }
    </style>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200 flex flex-col">

    <x-mary-toast />

    {{-- MAINTENANCE MODE BANNER --}}
    @if(\App\Models\Setting::get('maintenance_mode') && auth()->user() && (auth()->user()->hasRole(['Super Admin', 'VC']) || auth()->user()->can('bypass maintenance')))
        <div class="bg-error text-error-content text-center py-2 font-bold text-sm sticky top-0 z-50 shadow-md">
            <div class="flex items-center justify-center gap-2">
                <x-mary-icon name="o-exclamation-triangle" class="w-5 h-5" />
                <span>MAINTENANCE MODE IS ACTIVE - REGULAR USERS CANNOT ACCESS THE SITE</span>
            </div>
        </div>
    @endif

    {{-- HEADER --}}
    <nav class="navbar bg-base-100 border-b border-base-content/10 px-4 lg:px-10 justify-between sticky top-0 z-40 h-16 shrink-0">
        {{-- Left: Toggle, Logo & Title --}}
        <div class="flex items-center gap-4">
            {{-- Mobile Drawer Toggle --}}
            <label for="main-drawer" class="lg:hidden mr-2 btn btn-ghost btn-circle btn-sm">
                <x-mary-icon name="o-bars-3" />
            </label>
            
            {{-- Desktop Sidebar Toggle Marker --}}
            <div id="sidebar-toggle-marker" class="hidden lg:block"></div>

            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
                 @if($logo = \App\Models\Setting::get('logo_url'))
                    <img src="{{ $logo }}" class="w-8 h-8 object-contain" />
                @else
                    <x-app-logo class="w-8 h-8 text-primary" />
                @endif
                <span class="font-bold text-lg tracking-tight hidden md:block">
                    {{ \App\Models\Setting::get('site_name', config('app.name')) }}
                </span>
            </a>
        </div>

        {{-- Center: Page Title --}}
        <div class="hidden md:flex flex-col items-start absolute left-1/2 transform -translate-x-1/2">
            <h1 class="font-bold text-xl">{{ $title ?? '' }}</h1>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <button class="btn btn-ghost btn-circle btn-sm" @click.stop="$dispatch('mary-search-open')" title="Search (Ctrl+G)">
                <x-mary-icon name="o-magnifying-glass" class="w-5 h-5" />
            </button>

            {{-- Theme Toggle --}}
            @if(\App\Models\Setting::get('enable_theme_toggle', true))
                <x-mary-theme-toggle class="btn btn-ghost btn-circle btn-sm" />
            @endif

            {{-- User Dropdown --}}
            @if($user = auth()->user())
                <x-mary-dropdown label="{{ $user->name }}" class="btn-ghost btn-sm" right>
                    <x-mary-menu-item title="Profile" icon="o-user" link="{{ route('profile.edit') }}" />
                    <x-mary-menu-item title="Logout" icon="o-power" onclick="document.getElementById('logout-form').submit()" />
                </x-mary-dropdown>
                <form method="POST" action="{{ route('logout') }}" class="hidden" id="logout-form">
                    @csrf
                </form>
            @endif
        </div>
    </nav>

    {{-- MAIN LAYOUT --}}
    <x-mary-main drawer="main-drawer" collapsible class="flex-1 overflow-hidden">
        
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" class="bg-base-100 border-r border-base-content/10 w-72 h-full">
            
            {{-- TOGGLE BUTTON (Teleported to Header) --}}
            <template x-teleport="#sidebar-toggle-marker">
                <button @click="toggle" class="btn btn-ghost btn-circle btn-sm mr-2" title="Toggle Sidebar">
                    <x-mary-icon name="o-bars-3" class="w-5 h-5" />
                </button>
            </template>

            <div class="flex flex-col h-full">

                {{-- 2. SCROLLABLE MENU AREA --}}
                {{-- FIX: Added overflow-visible when collapsed so popups aren't cut off --}}
                <div class="flex-1 no-scrollbar py-6 w-full transition-all duration-300" 
                     :class="collapsed ? 'px-0 overflow-visible' : 'px-3 overflow-y-auto'">
                    
                    <x-mary-menu activate-by-route>

                        {{-- Section: Platform --}}
                        @can('view dashboard')
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-2 mb-2 mary-hideable">Platform</div>
                            <x-mary-menu-item route="dashboard" icon="o-home" title="Dashboard" />
                        @endcan

                        {{-- Section: Meetings --}}
                        @if(auth()->user()->canAny(['view meetings', 'view agenda items', 'view minutes', 'view notifications', 'view announcements']))
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-8 mb-2 mary-hideable">Meeting Management</div>

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
                            <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-8 mb-2 mary-hideable">Administration</div>

                            {{-- USER MANAGEMENT (Custom Popover Logic) --}}
                            @if(auth()->user()->canAny(['view users', 'view roles', 'view permissions', 'view positions', 'view employment statuses']))
                                <li x-data="{ open: false, hover: false }" 
                                    @mouseenter="hover = true" 
                                    @mouseleave="hover = false" 
                                    class="relative"
                                    :class="hover && collapsed ? 'z-50' : 'z-0'"
                                >
                                    <details :open="open || (collapsed && hover)" class="group" @click.prevent="if(!collapsed) open = !open">
                                        <summary 
                                            class="hover:text-inherit px-4 py-1.5 my-0.5 text-inherit cursor-pointer list-none rounded-md transition-colors duration-200"
                                            :class="{ 'bg-base-300': (open && !collapsed) || (collapsed && hover) }"
                                        >
                                            <div class="flex items-center gap-3">
                                                <x-mary-icon name="o-users" class="inline-flex my-0.5" />
                                                <span class="mary-hideable whitespace-nowrap truncate font-medium">User Management</span>
                                                
                                            </div>
                                        </summary>

                                        {{-- Submenu: Absolute when collapsed, Static when expanded --}}
                                        <ul class="font-normal"
                                            :class="collapsed 
                                                ? 'absolute left-full top-0 ml-3 bg-base-100 shadow-xl rounded-lg w-60 border border-base-300 p-2' 
                                                : 'mary-hideable'"
                                        >
                                            {{-- Title shown only in popover --}}
                                            <li x-show="collapsed" class="menu-title border-b border-base-content/10 mb-2 pb-2 text-base-content font-bold px-2">User Management</li>
                                            
                                            @can('view users') <x-mary-menu-item route="users.index" title="Users" icon="o-user" /> @endcan
                                            @can('view roles') <x-mary-menu-item route="roles.index" title="Roles" icon="o-shield-check" /> @endcan
                                            @can('view permissions') <x-mary-menu-item route="permissions.index" title="Permissions" icon="o-key" /> @endcan
                                            @can('view positions') <x-mary-menu-item route="positions.index" title="Positions" icon="o-briefcase" /> @endcan
                                            @can('view employment statuses') <x-mary-menu-item route="employment-statuses.index" title="Statuses" icon="o-check-badge" /> @endcan
                                        </ul>
                                    </details>
                                </li>
                            @endif

                            {{-- SETTINGS (Custom Popover Logic) --}}
                            @if(auth()->user()->canAny(['view settings', 'view meeting types', 'view agenda item types', 'view help categories', 'view help articles']))
                                <li x-data="{ open: false, hover: false }" 
                                    @mouseenter="hover = true" 
                                    @mouseleave="hover = false" 
                                    class="relative"
                                    :class="hover && collapsed ? 'z-50' : 'z-0'"
                                >
                                    <details :open="open || (collapsed && hover)" class="group" @click.prevent="if(!collapsed) open = !open">
                                        <summary 
                                            class="hover:text-inherit px-4 py-1.5 my-0.5 text-inherit cursor-pointer list-none rounded-md transition-colors duration-200"
                                            :class="{ 'bg-base-300': (open && !collapsed) || (collapsed && hover) }"
                                        >
                                            <div class="flex items-center gap-3">
                                                <x-mary-icon name="o-cog-6-tooth" class="inline-flex my-0.5" />
                                                <span class="mary-hideable whitespace-nowrap truncate font-medium">Settings</span>
                                            </div>
                                        </summary>

                                        <ul class="font-normal"
                                            :class="collapsed 
                                                ? 'absolute left-full top-0 ml-3 bg-base-100 shadow-xl rounded-lg w-60 border border-base-300 p-2' 
                                                : 'mary-hideable'"
                                        >
                                            <li x-show="collapsed" class="menu-title border-b border-base-content/10 mb-2 pb-2 text-base-content font-bold px-2">Settings</li>

                                            @can('view settings') <x-mary-menu-item route="settings.system" title="System" icon="o-adjustments-horizontal" /> @endcan
                                            @can('view meeting types') <x-mary-menu-item route="meeting-types.index" title="Meeting Types" icon="o-tag" /> @endcan
                                            @can('view agenda item types') <x-mary-menu-item route="agenda-item-types.index" title="Agenda Types" icon="o-bookmark" /> @endcan
                                            @can('view settings') <x-mary-menu-item route="settings.keywords" title="Keywords" icon="o-hashtag" /> @endcan
                                            @if(auth()->user()->can('view help categories') || auth()->user()->can('view help articles'))
                                                <x-mary-menu-item route="help.admin.index" title="Help Center" icon="o-lifebuoy" />
                                            @endif
                                        </ul>
                                    </details>
                                </li>
                            @endif
                        @endif

                        {{-- HELP SECTION --}}
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

            </div>
        </x-slot:sidebar>

        {{-- CONTENT --}}
        <x-slot:content class="px-5 py-8 lg:px-10 lg:py-10 flex flex-col h-full overflow-y-auto">
            <div class="flex-1 max-w-screen-2xl mx-auto w-full">
                {{ $slot }}
            </div>
            
            {{-- FOOTER --}}
            <footer class="mt-10 border-t border-base-content/10 pt-6 text-center text-sm text-base-content/50 pb-6">
                {{ \App\Models\Setting::get('footer_text') ?? 'Copyright © ' . date('Y') . ' ' . \App\Models\Setting::get('site_name', config('app.name')) }}
            </footer>
        </x-slot:content>
    </x-mary-main>

    <x-mary-spotlight 
        shortcut="ctrl.g"
        search-text="Find agenda, minutes and/or notifications..."
        no-results-text="Ops! Nothing here."
    />
</body>
</html>