<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 *
 * @property int $id Primary key, auto-incrementing.
 * @property int $asset_id Foreign key to the 'assets' table, linking to the asset being depreciated.
 * @property \Illuminate\Support\Carbon $depreciation_date The date on which this depreciation entry is recognized.
 * @property float $amount The amount of depreciation recorded for this period.
 * @property int|null $journal_entry_id Nullable foreign key to the 'journal_entries' table, linking to the actual posted financial transaction.
 * @property string $status The status of the depreciation entry (e.g., 'Draft', 'Posted').
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read \App\Models\Asset $asset The fixed asset to which this depreciation entry belongs.
 * @property-read \App\Models\JournalEntry|null $journalEntry The associated journal entry that records this depreciation.
 */
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
        'amount' => 'decimal:2', // Ensures precision for currency amounts
        'journal_entry_id' => 'integer',
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
     * Get the asset that this depreciation entry belongs to.
     * Each depreciation entry corresponds to a specific fixed asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
