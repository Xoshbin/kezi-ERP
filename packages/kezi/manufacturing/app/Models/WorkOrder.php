<?php

namespace Kezi\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Manufacturing\Enums\WorkOrderStatus;

/**
 * @property int $id
 * @property int $company_id
 * @property int $manufacturing_order_id
 * @property int $work_center_id
 * @property int $sequence
 * @property string $name
 * @property WorkOrderStatus $status
 * @property float|null $planned_duration
 * @property \Illuminate\Support\Carbon|null $planned_start_at
 * @property \Illuminate\Support\Carbon|null $planned_finished_at
 * @property float|null $actual_duration
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property-read ManufacturingOrder $manufacturingOrder
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Manufacturing\Models\WorkCenter $workCenter
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereActualDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereManufacturingOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder wherePlannedDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder wherePlannedFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder wherePlannedStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkOrder whereWorkCenterId($value)
 * @method static \Kezi\Manufacturing\Database\Factories\WorkOrderFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class WorkOrder extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Manufacturing\Database\Factories\WorkOrderFactory
    {
        return \Kezi\Manufacturing\Database\Factories\WorkOrderFactory::new();
    }

    protected $fillable = [
        'company_id',
        'manufacturing_order_id',
        'work_center_id',
        'sequence',
        'name',
        'status',
        'planned_duration',
        'planned_start_at',
        'planned_finished_at',
        'actual_duration',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'sequence' => 'integer',
            'planned_duration' => 'decimal:2',
            'planned_start_at' => 'datetime',
            'planned_finished_at' => 'datetime',
            'actual_duration' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<ManufacturingOrder, static>
     */
    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    /**
     * @return BelongsTo<WorkCenter, static>
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }
}
