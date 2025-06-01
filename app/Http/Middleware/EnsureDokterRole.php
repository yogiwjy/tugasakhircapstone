<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDokterRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isDokter()) {
            Auth::logout();
            return redirect()->route('filament.dokter.auth.login')
                ->withErrors(['email' => 'Akses ditolak. Hanya dokter yang diizinkan.']);
        }

        return $next($request);
    }
}