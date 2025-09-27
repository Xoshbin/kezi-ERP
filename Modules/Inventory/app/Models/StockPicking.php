<?php

namespace Modules\Inventory\Models;

use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}

