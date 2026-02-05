<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\HR\Casts\PayrollCurrencyMoneyCast;

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
 * @property-read \Kezi\HR\Models\Payroll $payroll
 * @property-read \Kezi\Accounting\Models\Account $account
 * @property-read \Kezi\Accounting\Models\AnalyticAccount|null $analyticAccount
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereAmountCompanyCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereAnalyticAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereDebitCredit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereIsStatutory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereIsTaxable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereLineType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine wherePayrollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PayrollLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
        'amount_company_currency' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
