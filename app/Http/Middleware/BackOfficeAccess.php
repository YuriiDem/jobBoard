<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackOfficeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = Filament::auth()->user()) {
            abort_unless($user->canAccessPanel(Filament::getPanel('admin')), 403);

            return $next($request);
        }

        abort_unless($request->routeIs('filament.admin.auth.login'), 404);

        if ($request->has('signature')) {
            abort_unless($request->hasValidSignature() && $request->filled('expires'), 404);
            $request->session()->put('back_office.login_until', (int) $request->query('expires'));

            return redirect()->route('filament.admin.auth.login')
                ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
        }

        abort_unless($request->session()->get('back_office.login_until', 0) > now()->timestamp, 404);

        return $next($request);
    }
}
