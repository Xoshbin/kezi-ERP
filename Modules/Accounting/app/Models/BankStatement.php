<?php

namespace Modules\Accounting\Models;

use Eloquent;
use Brick\Money\Money;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Modules\Accounting\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\BankStatementFactory;
use Modules\Accounting\Models\BankStatementLine;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;

/**
 * @property int $id
 * @property int $company_id
 * @property int $journal_id
 * @property string $reference
 * @property Carbon $date
 * @property Money $starting_balance
 * @property Money $ending_balance
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
 * @mixin Eloquent
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
        'starting_balance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'ending_balance' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
    ];

    public function company(): BelongsTo
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
