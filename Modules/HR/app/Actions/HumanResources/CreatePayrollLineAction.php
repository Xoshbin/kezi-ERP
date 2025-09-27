<?php

namespace Modules\HR\Actions\HumanResources;

use App\DataTransferObjects\HumanResources\PayrollLineDTO;
use App\Models\Currency;
use App\Models\Payroll;
use App\Models\PayrollLine;
use Brick\Money\Money;

class CreatePayrollLineAction
{
    public function execute(Payroll $payroll, PayrollLineDTO $dto): PayrollLine
    {
        $currency = $payroll->currency;

        // Convert Money fields if they're strings
        $rate = null;
        if ($dto->rate) {
            $rate = $dto->rate instanceof Money
                ? $dto->rate
                : Money::of($dto->rate, $currency->code);
        }

        $amount = $dto->amount instanceof Money
            ? $dto->amount
            : Money::of($dto->amount, $currency->code);

        // Calculate company currency amount if needed
        $amountCompanyCurrency = null;
        if ($payroll->currency_id !== $payroll->company->currency_id) {
            // TODO: Implement currency conversion using CurrencyConverterService
            // For now, set to same amount
            $amountCompanyCurrency = $amount;
        }

        return PayrollLine::create([
            'company_id' => $dto->company_id,
            'payroll_id' => $payroll->id,
            'account_id' => $dto->account_id,
            'line_type' => $dto->line_type,
            'code' => $dto->code,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit' => $dto->unit,
            'rate' => $rate,
            'amount' => $amount,
            'amount_company_currency' => $amountCompanyCurrency,
            'tax_rate' => $dto->tax_rate,
            'is_taxable' => $dto->is_taxable,
            'is_statutory' => $dto->is_statutory,
            'debit_credit' => $dto->debit_credit,
            'analytic_account_id' => $dto->analytic_account_id,
            'notes' => $dto->notes,
            'reference' => $dto->reference,
        ]);
    }
}
