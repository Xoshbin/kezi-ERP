<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $type
 * @property string $short_code
 * @property int|null $currency_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Currency|null $currency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JournalEntry> $journalEntries
 * @property-read int|null $journal_entries_count
 * @method static \Database\Factories\JournalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal uniqueShortCode(string $shortCode, int $companyId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereShortCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Journal whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Journal extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'journals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'short_code',
        'currency_id',
        'default_debit_account_id',
        'default_credit_account_id',
    ];

    public const TYPE_SALE = 'sale';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_BANK = 'bank';
    public const TYPE_CASH = 'cash';
    public const TYPE_MISCELLANEOUS = 'miscellaneous';

    public static function getTypes(): array
    {
        return [
            self::TYPE_SALE => 'Sale',
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_BANK => 'Bank',
            self::TYPE_CASH => 'Cash',
            self::TYPE_MISCELLANEOUS => 'Miscellaneous',
        ];
    }

        
    /**
     * Get the default debit account for this journal.
     */
    public function defaultDebitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_debit_account_id');
    }

    /**
     * Get the default credit account for this journal.
     */
    public function defaultCreditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_credit_account_id');
    }

    /**
     * Get the company that owns the journal.
     * A journal belongs to a specific company in a multi-company setup [3].
     *
     * @OA\Property(
     *     property="company",
     *     type="object",
     *     ref="#/components/schemas/Company"
     * )
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency associated with the journal.
     * A journal can operate in a specific currency [3].
     *
     * @OA\Property(
     *     property="currency",
     *     type="object",
     *     ref="#/components/schemas/Currency"
     * )
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the journal entries for the journal.
     * A journal can have many journal entries [3].
     *
     * @OA\Property(
     *     property="journal_entries",
     *     type="array",
     *     @OA\Items(ref="#/components/schemas/JournalEntry")
     * )
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * Define a unique scope for short_code within a company.
     * This is crucial to prevent duplicate short codes for journals within the same company [3].
     * Although this is typically enforced at the database migration level,
     * adding a method here can be useful for specific query needs if required later.
     */
    public function scopeUniqueShortCode(
        \Illuminate\Database\Eloquent\Builder $query,
        string $shortCode,
        int $companyId
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('short_code', $shortCode)->where('company_id', $companyId);
    }
}
