<?php

namespace Kezi\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Enums\Loans\FeeType;

/**
 * @property int $id
 * @property int $loan_id
 * @property \Illuminate\Support\Carbon $date
 * @property FeeType $type
 * @property \Brick\Money\Money $amount
 * @property bool $capitalize
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Kezi\Accounting\Models\LoanAgreement $loan
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereCapitalize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereLoanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LoanFeeLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class LoanFeeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'date',
        'type',
        'amount',
        'capitalize',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => \Kezi\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'capitalize' => 'boolean',
        'type' => FeeType::class,
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
