<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $company_id
 * @property int $journal_id
 * @property string $reference
 * @property \Illuminate\Support\Carbon $date
 * @property float $starting_balance
 * @property float $ending_balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Journal $journal
 * @method static \Database\Factories\BankStatementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereEndingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereJournalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereStartingBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankStatement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankStatement extends Model
{
    /** @use HasFactory<\Database\Factories\BankStatementFactory> */
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
        'starting_balance' => MoneyCast::class,
        'ending_balance' => MoneyCast::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function journal()
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
