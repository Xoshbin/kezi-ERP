<?php

namespace Kezi\Pos\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kezi\Foundation\Casts\MoneyCast;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;

class PosOrder extends Model
{
    protected $fillable = [
        'uuid',
        'pos_session_id',
        'company_id',
        'customer_id',
        'currency_id',
        'order_number',
        'status',
        'ordered_at',
        'total_amount',
        'total_tax',
        'sector_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'total_amount' => MoneyCast::class,
            'total_tax' => MoneyCast::class,
            'sector_data' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PosOrderLine::class);
    }
}
