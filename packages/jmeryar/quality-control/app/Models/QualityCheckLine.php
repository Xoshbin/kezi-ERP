<?php

namespace Jmeryar\QualityControl\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Foundation\Observers\AuditLogObserver;
use Jmeryar\QualityControl\Enums\QualityCheckType;

/**
 * @property int $id
 * @property int $quality_check_id
 * @property int $parameter_id
 * @property bool|null $result_pass_fail
 * @property float|null $result_numeric
 * @property string|null $result_text
 * @property string|null $result_image_path
 * @property bool|null $is_within_tolerance
 */
#[ObservedBy([AuditLogObserver::class])]
class QualityCheckLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_check_id',
        'parameter_id',
        'result_pass_fail',
        'result_numeric',
        'result_text',
        'result_image_path',
        'is_within_tolerance',
    ];

    protected $casts = [
        'result_pass_fail' => 'boolean',
        'result_numeric' => 'decimal:4',
        'is_within_tolerance' => 'boolean',
    ];

    /**
     * @return BelongsTo<QualityCheck, static>
     */
    public function qualityCheck(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class);
    }

    /**
     * @return BelongsTo<QualityInspectionParameter, static>
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(QualityInspectionParameter::class, 'parameter_id');
    }

    /**
     * Check if this line passed
     */
    public function isPassed(): bool
    {
        $checkType = $this->parameter->check_type;

        return match ($checkType) {
            QualityCheckType::PassFail => $this->result_pass_fail === true,
            QualityCheckType::Measure => $this->is_within_tolerance === true,
            QualityCheckType::TextInput => $this->result_text !== null && $this->result_text !== '',
            QualityCheckType::TakePhoto => $this->result_image_path !== null && $this->result_image_path !== '',
            QualityCheckType::Instructions => true, // Instructions always pass
        };
    }

    /**
     * Check if this line failed
     */
    public function isFailed(): bool
    {
        return ! $this->isPassed();
    }

    /**
     * Get the result value as a displayable string
     */
    public function getDisplayValue(): string
    {
        $checkType = $this->parameter->check_type;

        return match ($checkType) {
            QualityCheckType::PassFail => $this->result_pass_fail ? 'Pass' : 'Fail',
            QualityCheckType::Measure => $this->result_numeric.' '.($this->parameter->unit_of_measure ?? ''),
            QualityCheckType::TextInput => $this->result_text ?? '',
            QualityCheckType::TakePhoto => $this->result_image_path ? 'Image uploaded' : 'No image',
            QualityCheckType::Instructions => 'N/A',
        };
    }
}
