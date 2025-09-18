<?php

namespace App\Models;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Observers\StockMoveObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([StockMoveObserver::class])]
class StockMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'quantity',
        'from_location_id',
        'to_location_id',
        'move_type',
        'status',
        'move_date',
        'reference',
        'source_type',
        'source_id',
        'created_by_user_id',
        'picking_id',
    ];

    protected $casts = [
        'move_type' => StockMoveType::class,
        'status' => StockMoveStatus::class,
        'move_date' => 'date',
    ];

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
     * @return BelongsTo<StockLocation, static>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<StockLocation, static>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, static>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, static>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<StockPicking, static>
     */
    public function picking(): BelongsTo
    {
        return $this->belongsTo(StockPicking::class, 'picking_id');
    }
}
