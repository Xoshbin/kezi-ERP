<?php

namespace App\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use Database\Factories\BankStatementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $journal_id
 * @property string $reference
 * @property Carbon $date
 * @property \Brick\Money\Money $starting_balance
 * @property \Brick\Money\Money $ending_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 * @property-read Journal $journal
 * @property-read Currency $currency
 *
 * @method static BankStatementFactory factory($count = null, $state = [])
 * @method static Builder<static>|BankStatement newModelQuery()
 * @method static Builder<static>|BankStatement newQuery()
 * @method static Builder<static>|BankStatement query()
 * @method static Builder<static>|BankStatement whereCompanyId($value)
 * @method static Builder<static>|BankStatement whereCreatedAt($value)
 * @method static Builder<static>|BankStatement whereDate($value)
 * @method static Builder<static>|BankStatement whereEndingBalance($value)
 * @method static Builder<static>|BankStatement whereId($value)
 * @method static Builder<static>|BankStatement whereJournalId($value)
 * @method static Builder<static>|BankStatement whereReference($value)
 * @method static Builder<static>|BankStatement whereStartingBalance($value)
 * @method static Builder<static>|BankStatement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BankStatement extends Model
{
    /** @use HasFactory<BankStatementFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'journal_id',
        'currency_id',
        'reference',
        'date',
        'starting_balance',
        'ending_balance',
    ];

    protected $casts = [
        'date' => 'date',
        'starting_balance' => DocumentCurrencyMoneyCast::class,
        'ending_balance' => DocumentCurrencyMoneyCast::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * Get the currency for the bank statement.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bankStatementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
