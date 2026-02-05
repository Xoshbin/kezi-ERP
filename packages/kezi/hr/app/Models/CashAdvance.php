<?php

namespace Kezi\HR\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Observers\AuditLogObserver;
use Kezi\HR\Enums\CashAdvanceStatus;
use Kezi\Payment\Models\Payment;

/**
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $currency_id
 * @property string $advance_number
 * @property \Brick\Money\Money $requested_amount
 * @property \Brick\Money\Money|null $approved_amount
 * @property \Brick\Money\Money|null $disbursed_amount
 * @property string $purpose
 * @property \Illuminate\Support\Carbon|null $expected_return_date
 * @property CashAdvanceStatus $status
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $disbursed_at
 * @property \Illuminate\Support\Carbon|null $settled_at
 * @property int|null $approved_by_user_id
 * @property int|null $disbursed_by_user_id
 * @property int|null $disbursement_journal_entry_id
 * @property int|null $settlement_journal_entry_id
 * @property int|null $payment_id
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Employee $employee
 * @property-read Currency $currency
 * @property-read User|null $approvedBy
 * @property-read User|null $disbursedBy
 * @property-read JournalEntry|null $disbursementJournalEntry
 * @property-read JournalEntry|null $settlementJournalEntry
 * @property-read Payment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExpenseReport> $expenseReports
 * @property-read int|null $expense_reports_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereAdvanceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereApprovedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereDisbursedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereDisbursedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereDisbursedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereDisbursementJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereExpectedReturnDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereRequestedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereSettledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereSettlementJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CashAdvance whereUpdatedAt($value)
 * @method static \Kezi\HR\Database\Factories\CashAdvanceFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[ObservedBy([AuditLogObserver::class])]
class CashAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'currency_id',
        'advance_number',
        'requested_amount',
        'approved_amount',
        'disbursed_amount',
        'purpose',
        'expected_return_date',
        'status',
        'requested_at',
        'approved_at',
        'disbursed_at',
        'settled_at',
        'approved_by_user_id',
        'disbursed_by_user_id',
        'disbursement_journal_entry_id',
        'settlement_journal_entry_id',
        'payment_id',
        'notes',
    ];

    protected $casts = [
        'requested_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'approved_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'disbursed_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'expected_return_date' => 'date',
        'status' => CashAdvanceStatus::class,
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by_user_id');
    }

    public function disbursementJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'disbursement_journal_entry_id');
    }

    public function settlementJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'settlement_journal_entry_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }

    protected static function newFactory()
    {
        return \Kezi\HR\Database\Factories\CashAdvanceFactory::new();
    }
}
