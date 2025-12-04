<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && Setting::get('require_2fa', false) && ! $user->two_factor_secret) {
            if (! $request->routeIs('two-factor.*') && 
                ! $request->routeIs('logout') && 
                ! $request->routeIs('profile.edit') &&
                ! $request->routeIs('user-password.edit') &&
                ! $request->routeIs('appearance.edit')
            ) {
                 return redirect()->route('two-factor.show')->with('warning', 'Two-factor authentication is required by system policy.');
            }
        }

        return $next($request);
    }
}
