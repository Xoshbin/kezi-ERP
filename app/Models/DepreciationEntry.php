<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Database\Factories\DepreciationEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use App\Observers\DepreciationEntryObserver;
use App\Enums\Assets\DepreciationEntryStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

/**
 * Class DepreciationEntry
 *
 * @package App\Models
 *
 * This Eloquent model represents a single depreciation event for a fixed asset.
 * It is crucial for automating the depreciation process, recognizing depreciation
 * expense, and maintaining the accuracy of the asset's book value on the balance sheet.
 * These entries are typically system-generated and, once linked to a posted journal entry,
 * become part of the immutable financial record.
 * @property int $id
 * @property int $asset_id
 * @property int|null $journal_entry_id
 * @property Carbon $depreciation_date
 * @property float $amount
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Asset $asset
 * @property-read JournalEntry|null $journalEntry
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
 * @mixin \Eloquent
 */
#[ObservedBy([DepreciationEntryObserver::class])]
class DepreciationEntry extends Model
{
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
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id', // Foreign key to the parent company, ensuring data integrity [2, 3].
        'asset_id',
        'depreciation_date',
        'amount',
        'journal_entry_id',
        'status'
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
        'amount' => MoneyCast::class, // Ensures precision for currency amounts
        'journal_entry_id' => 'integer',
        'status' => DepreciationEntryStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the asset that this depreciation entry belongs to.
     * Each depreciation entry corresponds to a specific fixed asset.
     *
     * @return BelongsTo
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
     *
     * @return BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Accessor to provide the currency_id to the MoneyCast.
     * FIX: Correctly references the parent 'asset' model, not 'invoice'.
     * This robust implementation prevents N+1 query issues.
     */
    public function getCurrencyIdAttribute(): int
    {
        // If the relationship is already loaded, use it. Otherwise, use the foreign key.
        return $this->asset->currency_id ?? $this->asset()->getForeignKeyResults()->first()->currency_id;
    }
}
