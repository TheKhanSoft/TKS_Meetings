<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        if (Setting::get('maintenance_mode', false)) {
            
            // Always allow these routes
            if ($request->is('login') || 
                $request->is('logout') || 
                $request->is('two-factor-challenge') ||
                $request->is('livewire/*') || // Allow Livewire updates for login page
                $request->is('register') // Maybe allow register?
            ) {
                return $next($request);
            }

            $user = $request->user();

            // Allow Super Admins, VCs, or users with permission to bypass
            if ($user && ($user->hasRole(['Super Admin', 'VC']) || $user->can('bypass maintenance'))) {
                return $next($request);
            }

            abort(503, 'The system is currently in maintenance mode. Please try again later.');
        }

        return $next($request);
    }
}
