<?php

namespace Modules\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AnalyticAccount;
use Modules\HR\Casts\PayrollCurrencyMoneyCast;

/**
 * @property int $id
 * @property int $company_id
 * @property int $payroll_id
 * @property int $account_id
 * @property string $line_type
 * @property string $code
 * @property array $description
 * @property float $quantity
 * @property string|null $unit
 * @property \Brick\Money\Money|null $rate
 * @property \Brick\Money\Money $amount
 * @property \Brick\Money\Money|null $amount_company_currency
 * @property float|null $tax_rate
 * @property bool $is_taxable
 * @property bool $is_statutory
 * @property string $debit_credit
 * @property int|null $analytic_account_id
 * @property string|null $notes
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \Modules\HR\Models\Payroll $payroll
 * @property-read \Modules\Accounting\Models\Account $account
 * @property-read \Modules\Accounting\Models\AnalyticAccount|null $analyticAccount
 */
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
