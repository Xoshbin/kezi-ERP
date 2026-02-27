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
    /**
     * @return array<string, mixed>
     */
    public function getMasterData(User $user, ?Carbon $since = null, ?int $companyId = null, int $page = 1, int $limit = 500): array
    {
        $companyId = $companyId ?? (int) $user->companies()->value('companies.id') ?: null;

        if (! $companyId) {
            return [];
        }

        $applySince = fn (Builder $query) => $since ? $query->where('updated_at', '>=', $since) : $query;
        $byCompany = fn (Builder $query) => $query->where('company_id', $companyId);

        // Products: Company scoped, Paginated
        $productsPaginator = Product::query()
            ->tap($byCompany)
            ->where('is_active', true)
            ->tap($applySince)
            ->with(['company.currency', 'purchaseTaxes']) // Eager load helpful relations
            ->simplePaginate($limit, ['*'], 'page', $page);

        $products = $productsPaginator->items();

        // Customers: Paginated
        $customersPaginator = Partner::query()
            ->tap($byCompany) // Partner model has company_id
            ->tap($applySince)
            ->simplePaginate($limit, ['*'], 'page', $page);

        $customers = $customersPaginator->items();

        $hasMore = $productsPaginator->hasMorePages() || $customersPaginator->hasMorePages();

        $categories = [];
        $taxes = [];
        $profiles = [];
        $currencies = [];

        if ($page === 1) {
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
        }

        $company = \App\Models\Company::find($companyId);
        // Explicitly load currency if not loaded (though find usually doesn't load relations unless with)
        // But Company model might not have currency relation defined as 'currency', check Model?
        // Product model uses 'company.currency'. So likely Company has 'currency'.

        $company_currency = $company?->currency;

        return compact('products', 'categories', 'taxes', 'customers', 'profiles', 'currencies', 'company_currency', 'hasMore');
    }
}
