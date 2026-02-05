<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property int $days_overdue
 * @property string|null $email_subject
 * @property string|null $email_body
 * @property bool $print_letter
 * @property bool $send_email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Company $company
 * @property bool $charge_fee
 * @property \Brick\Money\Money $fee_amount
 * @property numeric $fee_percentage
 * @property int|null $fee_product_id
 * @property-read \Kezi\Product\Models\Product|null $feeProduct
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereChargeFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereDaysOverdue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereEmailBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereEmailSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereFeeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereFeePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereFeeProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel wherePrintLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereSendEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DunningLevel whereUpdatedAt($value)
 * @method static \Kezi\Accounting\Database\Factories\DunningLevelFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class DunningLevel extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\DunningLevelFactory
    {
        return \Kezi\Accounting\Database\Factories\DunningLevelFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'days_overdue',
        'email_subject',
        'email_body',
        'print_letter',
        'send_email',
        'charge_fee',
        'fee_amount',
        'fee_percentage',
        'fee_product_id',
    ];

    protected $casts = [
        'days_overdue' => 'integer',
        'print_letter' => 'boolean',
        'send_email' => 'boolean',
        'charge_fee' => 'boolean',
        'fee_amount' => \Kezi\Foundation\Casts\BaseCurrencyMoneyCast::class,
        'fee_percentage' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function feeProduct(): BelongsTo
    {
        return $this->belongsTo(\Kezi\Product\Models\Product::class, 'fee_product_id');
    }
}
