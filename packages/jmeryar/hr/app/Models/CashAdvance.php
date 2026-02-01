<?php

namespace Jmeryar\HR\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\HR\Enums\CashAdvanceStatus;
use Jmeryar\Payment\Models\Payment;

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
        'requested_amount' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'approved_amount' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'disbursed_amount' => \Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast::class,
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
        return \Jmeryar\HR\Database\Factories\CashAdvanceFactory::new();
    }
}
