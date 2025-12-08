{{-- TOGGLE BUTTON (Teleported to Header) --}}
<template x-teleport="#sidebar-toggle-marker">
    <x-mary-button @click="toggle" class="btn btn-ghost btn-circle btn-sm mr-2" tooltip-right="Toggle Sidebar">
        <x-mary-icon name="o-bars-3-bottom-left" class="w-5 h-5" x-show="collapsed" />
        <x-mary-icon name="o-x-mark" class="w-5 h-5" x-show="!collapsed" />
    </x-mary-button>
</template>

<div class="flex flex-col h-full">

    {{-- 2. SCROLLABLE MENU AREA --}}
    {{-- FIX: Added overflow-visible when collapsed so popups aren't cut off --}}
    <div class="flex-1 no-scrollbar py-2 w-full transition-all duration-300" 
            :class="collapsed ? 'px-0 overflow-visible' : 'px-1 overflow-y-auto'">
        
        <x-mary-menu activate-by-route>

            {{-- Section: Platform --}}
            @can('view dashboard')
                <div class="text-xs font-bold uppercase tracking-widest text-base-content/40 px-3 mt-2 mb-2 mary-hideable">Platform</div>
                <x-mary-menu-item route="dashboard" icon="o-home" title="Dashboard" tooltip-right="Dashboard" />
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