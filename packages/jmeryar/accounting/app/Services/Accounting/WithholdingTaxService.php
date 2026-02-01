<?php

namespace Jmeryar\Accounting\Services\Accounting;

use App\Models\Company;
use Brick\Money\Money;
use Jmeryar\Accounting\Actions\Accounting\ApplyWithholdingTaxAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\ApplyWithholdingTaxDTO;
use Jmeryar\Accounting\Enums\Accounting\WithholdingTaxApplicability;
use Jmeryar\Accounting\Models\WithholdingTaxEntry;
use Jmeryar\Accounting\Models\WithholdingTaxType;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Payment\Models\Payment;

class WithholdingTaxService
{
    public function __construct(
        private readonly ApplyWithholdingTaxAction $applyWithholdingTaxAction,
    ) {}

    /**
     * Apply withholding tax to a vendor payment.
     *
     * @return WithholdingTaxEntry|null Returns null if no WHT was applied
     */
    public function applyToPayment(
        Payment $payment,
        WithholdingTaxType $whtType,
        Money $baseAmount
    ): ?WithholdingTaxEntry {
        if (! $payment->paid_to_from_partner_id) {
            return null;
        }

        $dto = new ApplyWithholdingTaxDTO(
            company_id: $payment->company_id,
            payment_id: $payment->id,
            vendor_id: $payment->paid_to_from_partner_id,
            withholding_tax_type_id: $whtType->id,
            base_amount: $baseAmount,
            currency_id: $payment->currency_id,
        );

        return $this->applyWithholdingTaxAction->execute($dto);
    }

    /**
     * Get the applicable WHT type for a vendor based on company settings.
     * Returns the default active WHT type for the company.
     */
    public function getApplicableWHTType(Company $company, ?Partner $vendor = null): ?WithholdingTaxType
    {
        // Get the first active WHT type for this company
        // In the future, this could be extended to check vendor-specific settings
        return WithholdingTaxType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get WHT types applicable to services.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, WithholdingTaxType>
     */
    public function getServiceWHTTypes(Company $company): \Illuminate\Database\Eloquent\Collection
    {
        return WithholdingTaxType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('applicable_to', [
                WithholdingTaxApplicability::Services->value,
                WithholdingTaxApplicability::Both->value,
            ])
            ->get();
    }

    /**
     * Get WHT types applicable to goods.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, WithholdingTaxType>
     */
    public function getGoodsWHTTypes(Company $company): \Illuminate\Database\Eloquent\Collection
    {
        return WithholdingTaxType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('applicable_to', [
                WithholdingTaxApplicability::Goods->value,
                WithholdingTaxApplicability::Both->value,
            ])
            ->get();
    }

    /**
     * Calculate the journal entry lines for a WHT entry.
     * Returns array with debit (reduce AP) and credit (increase WHT Payable) lines.
     *
     * @return array{debit: array{account_id: int, amount: Money}, credit: array{account_id: int, amount: Money}}
     */
    public function getJournalEntryLines(WithholdingTaxEntry $entry): array
    {
        $whtType = $entry->withholdingTaxType;

        return [
            // The withheld amount is credited to the WHT Payable account
            // (This amount is owed to the tax authority)
            'credit' => [
                'account_id' => $whtType->withholding_account_id,
                'amount' => $entry->withheld_amount,
            ],
            // The debit side reduces what we owe the vendor
            // This is typically handled in the payment journal entry
            // as a reduction in the amount paid to the vendor
            'debit' => [
                'account_id' => $whtType->withholding_account_id,
                'amount' => $entry->withheld_amount,
            ],
        ];
    }
}
