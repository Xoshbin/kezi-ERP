<?php

namespace Kezi\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Kezi\Accounting\Enums\Deferred\DeferralMethod;
use Kezi\Foundation\Casts\BaseCurrencyMoneyCast;

/**
 * Class DeferredItem
 * Represents a revenue or expense that is deferred and recognized over time.
 *
 * Similar to a Fixed Asset, but for short-term deferrals (subscriptions, insurance).
 *
 * @property int $id
 * @property int $company_id
 * @property string $type
 * @property string $name
 * @property \Brick\Money\Money $original_amount
 * @property \Brick\Money\Money $deferred_amount
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property DeferralMethod $method
 * @property int $deferred_account_id
 * @property int $recognition_account_id
 * @property string|null $source_type
 * @property int|null $source_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Company $company
 * @property-read \Kezi\Accounting\Models\Account $deferredAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Kezi\Accounting\Models\DeferredLine> $lines
 * @property-read int|null $lines_count
 * @property-read \Kezi\Accounting\Models\Account $recognitionAccount
 * @property-read Model|\Eloquent|null $source
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereDeferredAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereDeferredAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereOriginalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereRecognitionAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeferredItem whereUpdatedAt($value)
 * @method static \Kezi\Accounting\Database\Factories\DeferredItemFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
class DeferredItem extends Model
{
    use HasFactory;

    protected static function newFactory(): \Kezi\Accounting\Database\Factories\DeferredItemFactory
    {
        return \Kezi\Accounting\Database\Factories\DeferredItemFactory::new();
    }

    protected $table = 'deferred_items';

    protected $fillable = [
        'company_id',
        'type', // 'revenue' or 'expense'
        'name',
        'original_amount',
        'deferred_amount',
        'start_date',
        'end_date',
        'method',
        'deferred_account_id',
        'recognition_account_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'original_amount' => BaseCurrencyMoneyCast::class,
        'deferred_amount' => BaseCurrencyMoneyCast::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'method' => DeferralMethod::class,
    ];

    protected $with = ['company.currency'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deferredAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deferred_account_id');
    }

    public function recognitionAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'recognition_account_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DeferredLine::class);
    }
}
