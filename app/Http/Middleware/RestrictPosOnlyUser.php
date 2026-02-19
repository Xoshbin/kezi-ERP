<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPosOnlyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Ensure Spatie team ID is set if we're not in a Filament tenant context
        if (getPermissionsTeamId() === null) {
            $companyId = $user->company_id ?: $user->companies()->first()?->id;
            if ($companyId) {
                setPermissionsTeamId($companyId);
            }
        }

        $isPosTerminalUrl = $request->is('pos') || $request->is('pos/*');

        // A "POS-only" user is one whose sole role is pos_cashier
        $isPosOnly = $user->hasRole('pos_cashier') && $user->roles()->count() === 1;

        // Redirect POS-only users away from any non-POS page
        if ($isPosOnly && ! $isPosTerminalUrl) {
            return redirect()->route('pos.terminal');
        }

        // Gate the POS terminal: requires access_pos_terminal permission OR super_admin role
        if ($isPosTerminalUrl) {
            if ($user->hasRole('super_admin')) {
                return $next($request);
            }

            if (! $user->hasPermissionTo('access_pos_terminal')) {
                abort(403, 'You do not have access to the POS terminal.');
            }
        }

        return $next($request);
    }
}
