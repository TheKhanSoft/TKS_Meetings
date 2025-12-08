<nav class="navbar bg-base-100 border-b border-base-content/10 px-4 lg:pr-6 justify-between sticky top-0 z-40 h-16 shrink-0">
    {{-- Left: Toggle, Logo & Title --}}
    <div class="flex items-center gap-2">
        {{-- Mobile Drawer Toggle --}}
        <label for="main-drawer" class="lg:hidden mr-0 btn btn-ghost btn-circle btn-sm">
            <x-mary-icon name="o-bars-3" />
        </label>
        
        {{-- Desktop Sidebar Toggle Marker --}}
        <div id="sidebar-toggle-marker" class="hidden lg:block"></div>

        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 pr-10" wire:navigate>
                @if($logo = \App\Models\Setting::get('logo_url'))
                <img src="{{ $logo }}" class="w-8 h-8 object-contain" />
            @else
                <x-app-logo class="w-8 h-8 text-primary" />
            @endif
            <span class="font-bold text-lg tracking-tight hidden md:block">
                {{ \App\Models\Setting::get('site_name', config('app.name')) }}
            </span>
        </a>
        <div class="ml-10 hidden md:block">
            <!-- @if($page_title ?? false)
                <div class="flex flex-col justify-center h-full">
                    <h1 class="text-xl font-bold leading-tight">{{ $page_title }}</h1>
                    @if($page_subtitle ?? false)
                        <div class="text-xs text-gray-500 leading-tight">{{ $page_subtitle }}</div>
                    @endif
                </div>
            @endif -->
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="flex items-center gap-3">
        {{-- Search --}}
        <x-mary-button class="btn btn-ghost btn-circle btn-sm" @click.stop="$dispatch('mary-search-open')" tooltip-left="Search all meetings (Ctrl+G)">
            <x-mary-icon name="o-magnifying-glass" class="w-5 h-5" />
        </x-mary-button>

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
