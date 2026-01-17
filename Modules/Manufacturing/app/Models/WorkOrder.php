<?php

namespace Modules\Manufacturing\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Manufacturing\Enums\WorkOrderStatus;

class WorkOrder extends Model
{
    use HasFactory;

    protected static function newFactory(): \Modules\Manufacturing\Database\Factories\WorkOrderFactory
    {
        return \Modules\Manufacturing\Database\Factories\WorkOrderFactory::new();
    }

    protected $fillable = [
        'company_id',
        'manufacturing_order_id',
        'work_center_id',
        'sequence',
        'name',
        'status',
        'planned_duration',
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
