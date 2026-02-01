<?php

namespace Jmeryar\Accounting\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Jmeryar\Accounting\Enums\Deferred\DeferralMethod;
use Jmeryar\Foundation\Casts\BaseCurrencyMoneyCast;

/**
 * Class DeferredItem
 * Represents a revenue or expense that is deferred and recognized over time.
 * Similar to a Fixed Asset, but for short-term deferrals (subscriptions, insurance).
 */
class DeferredItem extends Model
{
    use HasFactory;

    protected static function newFactory(): \Jmeryar\Accounting\Database\Factories\DeferredItemFactory
    {
        return \Jmeryar\Accounting\Database\Factories\DeferredItemFactory::new();
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
