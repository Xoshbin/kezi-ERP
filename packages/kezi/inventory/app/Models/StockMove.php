<?php

namespace Kezi\Inventory\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Observers\StockMoveObserver;

/**
 * @property int $id
 * @property int $company_id
 * @property StockMoveType $move_type
 * @property StockMoveStatus $status
 * @property \Illuminate\Support\Carbon $move_date
 * @property string|null $reference
 * @property string|null $description
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $picking_id
 * @property int|null $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read User|null $createdByUser
 * @property-read StockPicking|null $picking
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMoveProductLine> $productLines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMoveLine> $stockMoveLines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMoveValuation> $stockMoveValuations
 */
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
     *
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

    protected static function newFactory(): \Kezi\Inventory\Database\Factories\StockMoveFactory
    {
        return \Kezi\Inventory\Database\Factories\StockMoveFactory::new();
    }
}
