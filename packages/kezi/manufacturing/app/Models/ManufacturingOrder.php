<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Manufacturing\Enums\ManufacturingOrderStatus;
use Kezi\Product\Models\Product;

/**
 * @property int $id
 * @property int $company_id
 * @property string $number
 * @property int $product_id
 * @property float $quantity_to_produce
 * @property float $quantity_produced
 * @property int $source_location_id
 * @property int $destination_location_id
 * @property ManufacturingOrderStatus $status
 * @property \Carbon\Carbon|null $planned_start_date
 * @property \Carbon\Carbon|null $planned_end_date
 * @property \Carbon\Carbon|null $actual_start_date
 * @property \Carbon\Carbon|null $actual_end_date
 * @property-read \Illuminate\Database\Eloquent\Collection|\Kezi\Manufacturing\Models\ManufacturingOrderLine[] $lines
 * @property-read \Illuminate\Database\Eloquent\Collection|\Kezi\Manufacturing\Models\WorkOrder[] $workOrders
 */
class ManufacturingOrder extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\ManufacturingOrderFactory
    {
        return \Kezi\Manufacturing\Database\Factories\ManufacturingOrderFactory::new();
    }

    protected $fillable = [
        'company_id',
        'number',
        'bom_id',
        'product_id',
        'quantity_to_produce',
        'quantity_produced',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'source_location_id',
        'destination_location_id',
        'journal_entry_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_to_produce' => 'decimal:4',
            'quantity_produced' => 'decimal:4',
            'status' => ManufacturingOrderStatus::class,
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_start_date' => 'datetime',
            'actual_end_date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<BillOfMaterial, static>
     */
    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * @return BelongsTo<Product, static>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'source_location_id');
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'destination_location_id');
    }

    /**
     * @return BelongsTo<JournalEntry, static>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\Kezi\QualityControl\Models\QualityCheck>
     */
    public function qualityChecks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Kezi\QualityControl\Models\QualityCheck::class, 'source');
    }

    /**
     * @return HasMany<ManufacturingOrderLine, static>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ManufacturingOrderLine::class);
    }

    /**
     * @return HasMany<WorkOrder, static>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
