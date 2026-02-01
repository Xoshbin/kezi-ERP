<?php

namespace Jmeryar\HR\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Foundation\Observers\AuditLogObserver;

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
        'amount' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
        return \Jmeryar\HR\Database\Factories\ExpenseReportLineFactory::new();
    }
}
