<?php

namespace Modules\HR\Models;

use App\Models\Company;
use Modules\HR\Models\Payroll;
use Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\HR\Casts\PayrollCurrencyMoneyCast;
use Modules\Foundation\Casts\BaseCurrencyMoneyCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends Model
{
    protected $fillable = [
        'company_id',
        'payroll_id',
        'account_id',
        'line_type',
        'code',
        'description',
        'quantity',
        'unit',
        'rate',
        'amount',
        'amount_company_currency',
        'tax_rate',
        'is_taxable',
        'is_statutory',
        'debit_credit',
        'analytic_account_id',
        'notes',
        'reference',
    ];

    protected $casts = [
        'description' => 'array',
        'quantity' => 'decimal:4',
        'rate' => PayrollCurrencyMoneyCast::class,
        'amount' => PayrollCurrencyMoneyCast::class,
        'amount_company_currency' => \Modules\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'tax_rate' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function analyticAccount(): BelongsTo
    {
        return $this->belongsTo(AnalyticAccount::class);
    }
}
