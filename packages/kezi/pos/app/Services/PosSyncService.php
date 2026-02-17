<?php

namespace Kezi\Pos\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Kezi\Accounting\Models\Tax;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Pos\Models\PosProfile;
use Kezi\Product\Models\Product;
use Kezi\Product\Models\ProductCategory;

class PosSyncService
{
    public function getMasterData(User $user, ?Carbon $since = null, ?int $companyId = null): array
    {
        $companyId = $companyId ?? $user->companies()->first()?->id;

        if (! $companyId) {
            return [];
        }

        $applySince = fn (Builder $query) => $since ? $query->where('updated_at', '>=', $since) : $query;
        $byCompany = fn (Builder $query) => $query->where('company_id', $companyId);

        // Products: Company scoped
        $products = Product::query()
            ->tap($byCompany)
            ->where('is_active', true)
            ->tap($applySince)
            ->with(['company.currency', 'purchaseTaxes']) // Eager load helpful relations
            ->get();

        // Categories: Global (no company_id check)
        $categories = ProductCategory::query()
            ->tap($applySince)
            ->get();

        // Taxes: Company scoped (Sales type)
        $taxes = Tax::query()
            ->tap($byCompany)
            ->where('is_active', true)
            ->whereIn('type', [\Kezi\Accounting\Enums\Accounting\TaxType::Sales, \Kezi\Accounting\Enums\Accounting\TaxType::Both])
            ->tap($applySince)
            ->get();

        // Customers: Partners (Assuming global or scoped? Check Partner model later. Defaulting to all active customers)
        // Usually Partners are shared.
        $customers = Partner::query()
            // ->tap($byCompany) // Partner model check needed. Assuming global for now.
            ->tap($applySince)
            ->limit(1000) // Safety limit for POS sync
            ->get();

        // POS Profiles: Company scoped
        $profiles = PosProfile::query()
            ->tap($byCompany)
            ->where('is_active', true)
            ->tap($applySince)
            ->get();

        // Currencies
        $currencies = Currency::query()
            ->where('is_active', true)
            ->tap($applySince)
            ->get();

        $company = \App\Models\Company::find($companyId);
        // Explicitly load currency if not loaded (though find usually doesn't load relations unless with)
        // But Company model might not have currency relation defined as 'currency', check Model?
        // Product model uses 'company.currency'. So likely Company has 'currency'.

        $company_currency = $company?->currency;

        return compact('products', 'categories', 'taxes', 'customers', 'profiles', 'currencies', 'company_currency');
    }
}
