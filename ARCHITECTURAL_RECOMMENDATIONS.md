# Architectural Recommendations & FAQ

## 1. Should we use Inertia.js as "Glue"?

**Short Answer:** **Yes**, it is a very strong candidate for your specific situation.

### Why Inertia fits well here:
*   **Solves the API Gap:** Inertia allows you to build a single-page app (React/Vue) using your existing Laravel routing and controllers. You do *not* need to build a full REST API (JSON Resources) immediately. You can pass your existing Eloquent models (or DTOs) directly to the frontend.
*   **Authentication:** Inertia uses standard Laravel Session authentication (just like Filament). You would not need to implement Sanctum or JWT logic immediately.
*   **Validation:** It shares Laravel's validation logic directly with the frontend.

### The Catch:
*   **Coupling:** Inertia apps are tightly coupled to the backend. If you ever wanted a native mobile app (iOS/Android), you would *still* need a separate JSON API. Inertia is great for Web UIs, but not for generic 3rd party integrations.
*   **Current State:** Your repo does not have Inertia installed. You would need to set up the client-side build process (Vite + React/Vue) alongside the existing Filament build.

---

## 2. Sanctum & Tenancy

**Q: Does Sanctum support Tenancy?**
Yes. However, Sanctum itself is just an authentication mechanism (Tokens). It doesn't know about "Companies" or "Tenants".

**Q: Can we use Sanctum with Filament resources?**
*   **Technically:** Yes, but it's complex. Filament is designed for Session-based auth. Using Sanctum tokens to access Filament pages is not a standard use case.
*   **Recommended Approach:** Keep Filament on Session Auth (for Backoffice/Admin). Use Sanctum *only* for your new separate API/Frontend.

**How to implement Tenant-Aware Sanctum:**
Since `Filament::getTenant()` won't work for API requests (because there is no Filament session), you must write a simple middleware:

```php
// app/Http/Middleware/ApiTenantMiddleware.php
public function handle($request, $next)
{
    $companyId = $request->header('X-Company-ID');

    if (!$companyId || !$request->user()->canAccessCompany($companyId)) {
        abort(403, 'Invalid Tenant Header');
    }

    // Globally set the tenant for this request
    app()->instance('currentTenant', Company::find($companyId));
    setPermissionsTeamId($companyId); // Critical for Spatie

    return $next($request);
}
```

---

## 3. RBAC Strategy (Spatie vs. Custom)

**Q: Recommendation?**
**Stick with Spatie Laravel Permission.**

### Reasons:
1.  **Standardization:** Any new developer you hire likely knows Spatie. A custom RBAC system is a huge technical debt risk.
2.  **Team Support:** You are already using `team_id` (via `setPermissionsTeamId`), which is the correct way to handle multi-tenant permissions.
3.  **Filament Integration:** `bezhansalleh/filament-shield` (installed in your `composer.json`) is deeply integrated with Spatie. Ripping this out would break your current Admin UI.

**The "Field Visibility" Problem:**
Spatie handles *Model* access ("Can I view products?"). It does not handle *Field* access ("Can I see the cost price?").
**Do not** replace Spatie. Instead, build a small layer *on top* of it, as recommended in the previous report (Strategy B: The Registry). Use Spatie roles to drive that registry.

---

## 4. Refactoring for the Future

To make your current setup ready for a new interface while keeping Filament working:

1.  **Add `HasApiTokens` to User Model:**
    Update `app/Models/User.php` to include `use Laravel\Sanctum\HasApiTokens;`. This allows you to issue tokens for the new UI or Mobile App later.

2.  **Decouple Middleware:**
    Currently, `App\Http\Middleware\SetPermissionsTeamId` relies on `Filament::getTenant()`.
    *   **Refactor:** Change this middleware to check *two* places:
        1.  Check `Filament::getTenant()` (for legacy/admin access).
        2.  If null, check `request()->header('X-Company-ID')` (for API access).
    *   This makes the same middleware work for both interfaces.

3.  **Centralize "Preparation Logic":**
    Move the logic currently inside `CreateJournalEntry.php` (Filament Page) into a **Service Class** (e.g., `JournalEntryPreparationService`).
    *   **Old Flow:** Filament Page -> Logic -> Action
    *   **New Flow:** Filament Page -> Service -> Action
    *   **New API Flow:** API Controller -> Service -> Action

    This ensures your new UI doesn't have to rewrite the complex math logic.
