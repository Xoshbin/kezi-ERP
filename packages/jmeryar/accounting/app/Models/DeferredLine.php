<?php

namespace Jmeryar\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast;

/**
 * Class DeferredLine
 * Represents a scheduled recognition entry (monthly installment).
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
