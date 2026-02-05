<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof \App\Models\Company && ! $tenant->onboarding_completed_at) {
            // Check if we are already on a page that should be accessible during onboarding
            // For now, let's assume if they have a tenant but not onboarded, we redirect to a specific onboarding page
            // Or if we want to reuse RegisterCompany, it might be tricky because it's for CREATING a tenant.

            // If they already have a company but it's not onboarded, we might need a "Finish Onboarding" page.
            // But the user's requirement is mostly about the initial creation.

            // For now, let's just log or handle the case where they have a company but it's not marked as complete.
        }

        return $next($request);
    }
}
