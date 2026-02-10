<?php

namespace Kezi\HR\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kezi\Accounting\Models\Account;

class DeductionRule extends Model
{
    /** @use HasFactory<\Kezi\HR\Database\Factories\DeductionRuleFactory> */
    use HasFactory;

    protected static function newFactory(): \Kezi\HR\Database\Factories\DeductionRuleFactory
    {
        return \Kezi\HR\Database\Factories\DeductionRuleFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'value',
        'amount',
        'currency_code',
        'is_statutory',
        'is_active',
        'liability_account_id',
    ];

    protected $casts = [
        'is_statutory' => 'boolean',
        'is_active' => 'boolean',
        'value' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeStatutory(Builder $query): void
    {
        $query->where('is_statutory', true);
    }

    /**
     * Get the money object for fixed amount deductions
     */
    protected function moneyAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount ? Money::ofMinor($this->amount, $this->currency_code ?? 'USD') : null,
        );
    }
}
