<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <title>Maintenance Mode</title>
</head>
<body class="min-h-screen font-sans antialiased bg-base-200 flex items-center justify-center p-4">
    <div class="max-w-lg w-full text-center">
        
        {{-- Logo --}}
        <div class="mb-8 flex justify-center">
            @if($logo = \App\Models\Setting::get('logo_url'))
                <img src="{{ $logo }}" class="h-16 object-contain" alt="Logo" />
            @else
                <div class="bg-primary/10 p-4 rounded-full">
                    <x-mary-icon name="o-academic-cap" class="w-12 h-12 text-primary" />
                </div>
            @endif
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-content/5 overflow-hidden">
            {{-- Header Pattern --}}
            <div class="h-2 bg-primary w-full"></div>
            
            <div class="card-body items-center text-center p-8 sm:p-12">
                <div class="bg-warning/10 p-4 rounded-full mb-6">
                    <x-mary-icon name="o-wrench-screwdriver" class="w-12 h-12 text-warning" />
                </div>
                
                <h1 class="text-3xl font-black text-base-content mb-2">Under Maintenance</h1>
                
                <p class="text-base-content/70 text-lg mb-8">
                    We are currently performing scheduled maintenance to improve our services. We should be back shortly.
                </p>

                <div class="flex flex-col gap-2 w-full">
                    <a href="{{ url('/') }}" class="btn btn-primary btn-outline w-full">
                        <x-mary-icon name="o-arrow-path" class="w-4 h-4" />
                        Check Again
                    </a>
                    
                    @if($contact = \App\Models\Setting::get('contact_email'))
                        <a href="mailto:{{ $contact }}" class="btn btn-ghost btn-sm w-full text-base-content/50 font-normal">
                            Contact Support
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 text-sm text-base-content/40">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('organization_name', 'Academics Inc.') }}
        </div>
    </div>
</body>
</html>
