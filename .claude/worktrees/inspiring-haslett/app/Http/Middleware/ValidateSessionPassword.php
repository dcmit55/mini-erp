<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ValidateSessionPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check jika user login dan password sudah berubah
        if (Auth::check()) {
            $user = Auth::user();

            // Refresh user dari database untuk get latest data
            $user->refresh();

            // Jika password berubah (cache berbeda dengan DB), logout user
            if ($user->isPasswordChanged()) {
                Auth::logout();
                Session::flush();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Log activity
                \Log::info("Auto-logout user {$user->username} due to password change");

                return redirect()->route('login')
                    ->with('warning', 'Your password was changed by an administrator. Please login again with your new credentials.');
            }
        }

        return $next($request);
    }
}