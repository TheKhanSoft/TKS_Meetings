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
    @include('components.layouts.app.header')

    {{-- MAIN LAYOUT --}}
    <x-mary-main drawer="main-drawer" collapsible class="flex-1 overflow-hidden">
        
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" class="bg-base-100 border-r border-base-content/10 w-72 h-full">
            @include('components.layouts.app.sidebar-menu')
        </x-slot:sidebar>

        {{-- CONTENT --}}
        <x-slot:content class="px-5 flex flex-col h-full overflow-y-auto">
            <div class="flex-1 max-w-screen-2xl mx-auto w-full">
                {{ $slot }}
            </div>
            
            {{-- FOOTER --}}
            @include('components.layouts.app.footer')
        </x-slot:content>
    </x-mary-main>

    <x-mary-spotlight 
        shortcut="ctrl.g"
        search-text="Find agenda, minutes and/or notifications..."
        no-results-text="Ops! Nothing here."
    />
</body>
</html>
