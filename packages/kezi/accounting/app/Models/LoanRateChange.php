<?php

namespace Kezi\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $loan_id
 * @property \Illuminate\Support\Carbon $effective_date
 * @property float $annual_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Accounting\Models\LoanAgreement $loan
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereAnnualRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereEffectiveDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanRateChange whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LoanRateChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'effective_date', 'annual_rate',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'annual_rate' => 'float',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
