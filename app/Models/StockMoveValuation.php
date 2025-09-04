<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Enums\Inventory\ValuationMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMoveValuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'stock_move_id',
        'quantity',
        'cost_impact',
        'valuation_method',
        'move_type',
        'journal_entry_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'cost_impact' => BaseCurrencyMoneyCast::class,
        'valuation_method' => ValuationMethod::class,
    ];

    /**
     * The relationships that should always be loaded.
     * Eager-loading the `company.currency` relationship is critical because the `BaseCurrencyMoneyCast`
     * for monetary fields on this model depends on the currency context provided by the company.
     * Without this, any retrieval of a `StockMoveValuation` would fail when casting monetary values
     * due to the missing currency information, leading to a "currency_id on null" error.
     *
     * @var list<string>
     */
    protected $with = ['company.currency'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
