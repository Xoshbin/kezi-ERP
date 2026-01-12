<?php

namespace Modules\Accounting\Services\Accounting;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\FiscalPosition;
use Modules\Accounting\Models\Tax;
use Modules\Foundation\Models\Partner;

class FiscalPositionService
{
    /**
     * Determine the best matching fiscal position for a partner.
     */
    public function getFiscalPositionForPartner(Partner $partner): ?FiscalPosition
    {
        // 1. Check partner's explicit fiscal position
        if ($partner->fiscal_position_id) {
            return $partner->fiscalPosition;
        }

        // 2. Try to find an auto-applied fiscal position matching partner's criteria
        return FiscalPosition::query()
            ->autoApply()
            ->where('company_id', $partner->company_id)
            ->where(function ($query) use ($partner) {
                $query->whereNull('country')
                    ->orWhere('country', $partner->country);
            })
            // Zip code range mapping (simplified logic)
            ->where(function ($query) use ($partner) {
                $query->where(function ($q) {
                    $q->whereNull('zip_from')
                        ->whereNull('zip_to');
                })->orWhere(function ($q) use ($partner) {
                    if (! $partner->zip_code) {
                        return $q->whereRaw('1=0'); // Cannot match zip if partner has no zip
                    }
                    $q->where('zip_from', '<=', $partner->zip_code)
                        ->where('zip_to', '>=', $partner->zip_code);
                });
            })
            ->where(function ($query) use ($partner) {
                if (! $partner->tax_id) {
                    $query->where('vat_required', false);
                }
            })
            ->orderByRaw('CASE WHEN country IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN zip_from IS NOT NULL THEN 0 ELSE 1 END')
            ->first();
    }

    /**
     * Map a tax based on a fiscal position.
     */
    public function mapTax(?FiscalPosition $fiscalPosition, Tax $tax): Tax
    {
        if (! $fiscalPosition) {
            return $tax;
        }

        $mapping = $fiscalPosition->taxMappings()
            ->where('original_tax_id', $tax->id)
            ->first();

        return $mapping ? $mapping->mappedTax : $tax;
    }

    /**
     * Map an account based on a fiscal position.
     */
    public function mapAccount(?FiscalPosition $fiscalPosition, Account $account): Account
    {
        if (! $fiscalPosition) {
            return $account;
        }

        $mapping = $fiscalPosition->accountMappings()
            ->where('original_account_id', $account->id)
            ->first();

        return $mapping ? $mapping->mappedAccount : $account;
    }
}
