<?php

namespace Modules\Accounting\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use App\Casts\OriginalCurrencyMoneyCast;
use App\Observers\BankStatementLineObserver;
use Brick\Money\Money;
use Database\Factories\BankStatementLineFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
 *
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
 *
 * @mixin \Eloquent
 */
#[ObservedBy([BankStatementLineObserver::class])]
class BankStatementLine extends Model
{
    /** @use HasFactory<BankStatementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'bank_statement_id',
        'date',
        'description',
        'partner_id',
        'amount',
        'foreign_currency_id',
        'amount_in_foreign_currency',
        'is_reconciled',
        'payment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => DocumentCurrencyMoneyCast::class,
        'amount_in_foreign_currency' => OriginalCurrencyMoneyCast::class,
        'is_reconciled' => 'boolean',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `bankStatement.currency` relationship is critical because the `DocumentCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the parent bank statement.
     * The `foreignCurrency` relationship is also eager-loaded to support the `OriginalCurrencyMoneyCast`
     * for the `amount_in_foreign_currency` field when foreign currency transactions are present.
     * Without this, any retrieval of a `BankStatementLine` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['bankStatement.currency', 'foreignCurrency'];

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payment(): BelongsTo
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
     * Get the foreign currency associated with the bank statement line.
     * This is only populated when the original transaction was in a different currency
     * than the bank statement's main currency.
     */
    public function foreignCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'foreign_currency_id');
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
}
