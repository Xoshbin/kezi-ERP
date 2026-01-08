<?php

namespace Modules\QualityControl\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Foundation\Observers\AuditLogObserver;
use Modules\Product\Models\Product;
use Modules\QualityControl\Enums\QualityTriggerFrequency;
use Modules\QualityControl\Enums\QualityTriggerOperation;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property QualityTriggerOperation $trigger_operation
 * @property QualityTriggerFrequency $trigger_frequency
 * @property int|null $product_id
 * @property int $inspection_template_id
 * @property int|null $quantity_threshold
 * @property bool $is_blocking
 * @property bool $active
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityControlPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'trigger_operation',
        'trigger_frequency',
        'product_id',
        'inspection_template_id',
        'quantity_threshold',
        'is_blocking',
        'active',
    ];

    protected $casts = [
        'trigger_operation' => QualityTriggerOperation::class,
        'trigger_frequency' => QualityTriggerFrequency::class,
        'quantity_threshold' => 'integer',
        'is_blocking' => 'boolean',
        'active' => 'boolean',
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
     * @return BelongsTo<QualityInspectionTemplate, static>
     */
    public function inspectionTemplate(): BelongsTo
    {
        return $this->belongsTo(QualityInspectionTemplate::class, 'inspection_template_id');
    }

    /**
     * Check if this control point applies to a given product
     */
    public function appliesToProduct(int $productId): bool
    {
        // If product_id is null, applies to all products
        if ($this->product_id === null) {
            return true;
        }

        return $this->product_id === $productId;
    }

    /**
     * Check if this control point should trigger for a given quantity
     */
    public function shouldTriggerForQuantity(float $quantity): bool
    {
        if ($this->trigger_frequency !== QualityTriggerFrequency::PerQuantity) {
            return true;
        }

        if ($this->quantity_threshold === null) {
            return true;
        }

        return $quantity >= $this->quantity_threshold;
    }
}
