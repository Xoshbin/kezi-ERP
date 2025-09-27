<?php

namespace Modules\Inventory\Models;

use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Observers\StockMoveObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([StockMoveObserver::class])]
class StockMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'move_type',
        'status',
        'move_date',
        'reference',
        'description',
        'source_type',
        'source_id',
        'picking_id',
        'created_by_user_id',
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
     * @return HasMany<StockMoveProductLine, static>
     */
    public function productLines(): HasMany
    {
        return $this->hasMany(StockMoveProductLine::class);
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

    public function picking(): BelongsTo
    {
        return $this->belongsTo(StockPicking::class, 'picking_id');
    }

    /**
     * Get all stock move lines through product lines
     * @return HasManyThrough<StockMoveLine, StockMoveProductLine, static>
     */
    public function stockMoveLines(): HasManyThrough
    {
        return $this->hasManyThrough(StockMoveLine::class, StockMoveProductLine::class);
    }

    /**
     * @return HasMany<StockMoveValuation, static>
     */
    public function stockMoveValuations(): HasMany
    {
        return $this->hasMany(StockMoveValuation::class);
    }
}
