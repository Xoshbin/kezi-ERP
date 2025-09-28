<?php

namespace Modules\Inventory\Models;


use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Models\StockMoveLine;
use Modules\Inventory\Models\StockMoveValuation;
use Modules\Inventory\Models\StockMoveProductLine;
use Modules\Inventory\Observers\StockMoveObserver;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
     * @return MorphTo<Model, static>
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

    protected static function newFactory(): \Modules\Inventory\Database\Factories\StockMoveFactory
    {
        return \Modules\Inventory\Database\Factories\StockMoveFactory::new();
    }
}
