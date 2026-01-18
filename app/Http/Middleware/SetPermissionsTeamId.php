<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to sync Spatie Permission's team context with Filament's tenant.
 *
 * This is required because Spatie Permission uses a team_id (company_id in our case)
 * to scope role lookups. Without this, hasRole() and hasPermissionTo() checks fail
 * even when the role is correctly assigned in the database.
 *
 * Must be registered in tenantMiddleware() with isPersistent: true to ensure
 * it runs on all Livewire requests within the tenant context.
 */
class SetPermissionsTeamId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();
        $user = $request->user();

        \Illuminate\Support\Facades\Log::info('SetPermissionsTeamId Middleware Running', [
            'tenant' => $tenant ? $tenant->getKey() : 'null',
            'url' => $request->url(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'db_connection' => config('database.default'),
            'db_database' => config('database.connections.'.config('database.default').'.database'),
            'app_key' => config('app.key'),
        ]);

        if ($tenant) {
            setPermissionsTeamId($tenant->getKey());
            \Illuminate\Support\Facades\Log::info('Team ID set to: '.$tenant->getKey(), [
                'hasRole_super_admin' => $user?->hasRole('super_admin') ? 'YES' : 'NO',
                'getRoleNames' => $user?->getRoleNames()->toArray(),
                'current_team_id' => getPermissionsTeamId(),
            ]);
        }

        return $next($request);
    }
}
