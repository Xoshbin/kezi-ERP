<?php

namespace Kezi\HR\Actions\HumanResources;

use Brick\Money\Money;
use Kezi\HR\DataTransferObjects\HumanResources\PayrollLineDTO;
use Kezi\HR\Models\Payroll;
use Kezi\HR\Models\PayrollLine;

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
            $amountCompanyCurrency = app(\Kezi\Foundation\Services\CurrencyConverterService::class)->convert(
                $amount,
                $payroll->company->currency,
                $payroll->pay_date ?? $payroll->created_at ?? now(),
                $payroll->company
            );
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
