<?php

namespace Modules\Inventory\Models;


use App\Models\Company;
use Modules\Foundation\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\StockMove;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Enums\Inventory\StockPickingState;

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
