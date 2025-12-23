<?php

namespace Modules\Inventory\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;

/**
 * @property int $id
 * @property int $company_id
 * @property StockPickingType $type
 * @property StockPickingState $state
 * @property int|null $partner_id
 * @property \Carbon\Carbon|null $scheduled_date
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $reference
 * @property string|null $origin
 * @property int|null $created_by_user_id
 * @property-read Company $company
 * @property-read Partner|null $partner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMove> $stockMoves
 */
class StockPicking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'state',
        'partner_id',
        'scheduled_date',
        'completed_at',
        'reference',
        'origin',
        'created_by_user_id',
    ];

    protected $casts = [
        'type' => StockPickingType::class,
        'state' => StockPickingState::class,
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Company, static>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Partner, static>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return HasMany<StockMove, static>
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'picking_id');
    }

    protected static function newFactory(): \Modules\Inventory\Database\Factories\StockPickingFactory
    {
        return \Modules\Inventory\Database\Factories\StockPickingFactory::new();
    }
}
