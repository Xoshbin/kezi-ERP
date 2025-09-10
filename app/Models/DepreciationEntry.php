<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Observers\DepreciationEntryObserver;
use Database\Factories\DepreciationEntryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class DepreciationEntry
 *
 * @property int $id
 * @property int $asset_id
 * @property int|null $journal_entry_id
 * @property Carbon $depreciation_date
 * @property \Brick\Money\Money $amount
 * @property DepreciationEntryStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Asset $asset
 * @property-read JournalEntry|null $journalEntry
 *
 * @method static DepreciationEntryFactory factory($count = null, $state = [])
 * @method static Builder<static>|DepreciationEntry newModelQuery()
 * @method static Builder<static>|DepreciationEntry newQuery()
 * @method static Builder<static>|DepreciationEntry query()
 * @method static Builder<static>|DepreciationEntry whereAmount($value)
 * @method static Builder<static>|DepreciationEntry whereAssetId($value)
 * @method static Builder<static>|DepreciationEntry whereCreatedAt($value)
 * @method static Builder<static>|DepreciationEntry whereDepreciationDate($value)
 * @method static Builder<static>|DepreciationEntry whereId($value)
 * @method static Builder<static>|DepreciationEntry whereJournalEntryId($value)
 * @method static Builder<static>|DepreciationEntry whereStatus($value)
 * @method static Builder<static>|DepreciationEntry whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy([DepreciationEntryObserver::class])]
class DepreciationEntry extends Model
{
    /** @use HasFactory<\Database\Factories\DepreciationEntryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'depreciation_entries';

    /**
     * The attributes that are mass assignable.
     * These fields define the core properties of a depreciation event.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id', // Foreign key to the parent company, ensuring data integrity [2, 3].
        'asset_id',
        'depreciation_date',
        'amount',
        'journal_entry_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     * Ensures numerical values are treated as floats and dates as Carbon instances.
     * The 'status' field will often reflect the immutable nature once 'Posted'.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'depreciation_date' => 'date', // Casts to Carbon instance, focusing on date part
        'amount' => BaseCurrencyMoneyCast::class, // Ensures precision for currency amounts
        'journal_entry_id' => 'integer',
        'status' => DepreciationEntryStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `asset.company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the asset's company.
     * Without this, any retrieval of a `DepreciationEntry` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['asset.company.currency'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Depreciation entries are tightly linked to the assets they depreciate and the
    | journal entries that record the financial impact.
    */

    /**
     * Get the company that this depreciation entry belongs to.
     */
    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the asset that this depreciation entry belongs to.
     * Each depreciation entry corresponds to a specific fixed asset.
     */
    /**
     * @return BelongsTo<Asset, static>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the journal entry associated with this depreciation entry.
     * This link is crucial for financial traceability and auditability.
     * The 'journal_entry_id' is nullable while the depreciation entry is in 'Draft'
     * but becomes mandatory upon 'Posting' when the actual financial impact occurs.
     */
    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
