<?php

namespace Kezi\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $loan_id
 * @property int $sequence
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Brick\Money\Money $payment_amount
 * @property \Brick\Money\Money $principal_component
 * @property \Brick\Money\Money $interest_component
 * @property \Brick\Money\Money $outstanding_balance_after
 * @property bool $is_accrual_posted
 * @property bool $is_payment_posted
 * @property int|null $journal_entry_id_accrual
 * @property int|null $journal_entry_id_payment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Accounting\Models\LoanAgreement $loan
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereInterestComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereIsAccrualPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereIsPaymentPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereJournalEntryIdAccrual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereJournalEntryIdPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereOutstandingBalanceAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry wherePaymentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry wherePrincipalComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanScheduleEntry whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LoanScheduleEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'sequence', 'due_date', 'payment_amount', 'principal_component', 'interest_component', 'outstanding_balance_after',
        'is_accrual_posted', 'is_payment_posted', 'journal_entry_id_accrual', 'journal_entry_id_payment',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'principal_component' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'interest_component' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'outstanding_balance_after' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'is_accrual_posted' => 'boolean',
        'is_payment_posted' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
