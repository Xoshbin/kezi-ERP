<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;

/**
 * Class DeferredLine
 * Represents a scheduled recognition entry (monthly installment).
 *
 * @property int $id
 * @property int $company_id
 * @property int $deferred_item_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Brick\Money\Money $amount
 * @property int|null $journal_entry_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company|null $company
 * @property-read \Kezi\Accounting\Models\DeferredItem $deferredItem
 * @property-read \Kezi\Accounting\Models\JournalEntry|null $journalEntry
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereDeferredItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereJournalEntryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DeferredLine extends Model
{
    use HasFactory;

    protected $table = 'deferred_lines';

    protected $fillable = [
        'company_id',
        'deferred_item_id',
        'date',
        'amount',
        'journal_entry_id',
        'status', // draft, posted
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => BaseCurrencyMoneyCast::class,
        'journal_entry_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // IMPORTANT: Required for BaseCurrencyMoneyCast to work, as it needs the Company context
    // inherited from the parent Item.
    protected $with = ['deferredItem.company.currency'];

    public function deferredItem(): BelongsTo
    {
        return $this->belongsTo(DeferredItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
