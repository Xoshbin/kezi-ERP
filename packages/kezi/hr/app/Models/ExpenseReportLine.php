<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Partner;
use Kezi\Foundation\Observers\AuditLogObserver;

/**
 * @property int $id
 * @property int $expense_report_id
 * @property int $expense_account_id
 * @property int|null $partner_id
 * @property string $description
 * @property \Illuminate\Support\Carbon $expense_date
 * @property \Brick\Money\Money $amount
 * @property string|null $receipt_reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ExpenseReport $expenseReport
 * @property-read Account $expenseAccount
 * @property-read Partner|null $partner
 * @property int $company_id
 * @property-read Company $company
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereExpenseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereExpenseReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine wherePartnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereReceiptReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpenseReportLine whereUpdatedAt($value)
 * @method static \Kezi\HR\Database\Factories\ExpenseReportLineFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class ExpenseReportLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'expense_report_id',
        'expense_account_id',
        'partner_id',
        'description',
        'expense_date',
        'amount',
        'receipt_reference',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function expenseReport(): BelongsTo
    {
        return $this->belongsTo(ExpenseReport::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\ExpenseReportLineFactory::new();
    }
}
