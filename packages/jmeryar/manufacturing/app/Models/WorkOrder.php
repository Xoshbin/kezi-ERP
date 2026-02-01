<?php

namespace Jmeryar\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Manufacturing\Enums\WorkOrderStatus;

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
 */
class WorkOrder extends Model
{
    use HasFactory;

    protected static function newFactory(): \Jmeryar\Manufacturing\Database\Factories\WorkOrderFactory
    {
        return \Jmeryar\Manufacturing\Database\Factories\WorkOrderFactory::new();
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
