<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Brick\Money\Money;
use Database\Factories\BankStatementLineFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use App\Observers\BankStatementLineObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * @property int $id
 * @property int $bank_statement_id
 * @property Carbon $date
 * @property string $description
 * @property string|null $partner_name
 * @property Money $amount
 * @property bool $is_reconciled
 * @property int|null $payment_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BankStatement $bankStatement
 * @property-read Payment|null $payment
 * @method static BankStatementLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|BankStatementLine newModelQuery()
 * @method static Builder<static>|BankStatementLine newQuery()
 * @method static Builder<static>|BankStatementLine query()
 * @method static Builder<static>|BankStatementLine whereAmount($value)
 * @method static Builder<static>|BankStatementLine whereBankStatementId($value)
 * @method static Builder<static>|BankStatementLine whereCreatedAt($value)
 * @method static Builder<static>|BankStatementLine whereDate($value)
 * @method static Builder<static>|BankStatementLine whereDescription($value)
 * @method static Builder<static>|BankStatementLine whereId($value)
 * @method static Builder<static>|BankStatementLine whereIsReconciled($value)
 * @method static Builder<static>|BankStatementLine wherePartnerName($value)
 * @method static Builder<static>|BankStatementLine wherePaymentId($value)
 * @method static Builder<static>|BankStatementLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[ObservedBy([BankStatementLineObserver::class])]
class BankStatementLine extends Model
{
    /** @use HasFactory<BankStatementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'date',
        'description',
        'partner_id',
        'amount',
        'is_reconciled',
        'payment_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => MoneyCast::class,
        'is_reconciled' => 'boolean'
    ];
    public function bankStatement()
    {
        return $this->belongsTo(BankStatement::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the partner associated with the bank statement line.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the journal entry that was created for this bank statement line (if any).
     * This uses the polymorphic relationship from JournalEntry.
     */
    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'source_id')
            ->where('source_type', self::class);
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * This makes the model responsible for knowing its own currency context.
     */
    public function getCurrencyIdAttribute(): int
    {
        // This assumes the 'bankStatement' relationship is always loaded when needed.
        // You can add loadMissing('bankStatement') for robustness if necessary.
        return $this->bankStatement->currency_id;
    }
}
