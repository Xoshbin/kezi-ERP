<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use App\Observers\BankStatementLineObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * @property int $id
 * @property int $bank_statement_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $description
 * @property string|null $partner_name
 * @property \Brick\Money\Money $amount
 * @property bool $is_reconciled
 * @property int|null $payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BankStatement $bankStatement
 * @property-read \App\Models\Payment|null $payment
 * @method static \Database\Factories\BankStatementLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereBankStatementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereIsReconciled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine wherePartnerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatementLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */

#[ObservedBy([BankStatementLineObserver::class])]
class BankStatementLine extends Model
{
    /** @use HasFactory<\Database\Factories\BankStatementLineFactory> */
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
    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\HasOne
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
