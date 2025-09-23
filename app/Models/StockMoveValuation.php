<?php

namespace App\Models;

use App\Casts\BaseCurrencyMoneyCast;
use App\Enums\Inventory\CostSource;
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
        'cost_source',
        'cost_source_reference',
        'cost_warnings',
    ];

    protected $casts = [
        'cost_impact' => BaseCurrencyMoneyCast::class,
        'valuation_method' => ValuationMethod::class,
        'cost_source' => CostSource::class,
        'cost_warnings' => 'array',
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

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockMove, static>
     */
    public function stockMove(): BelongsTo
    {
        return $this->belongsTo(StockMove::class);
    }

    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
