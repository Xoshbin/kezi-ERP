<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Enums\Loans\FeeType;

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
        'amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'capitalize' => 'boolean',
        'type' => FeeType::class,
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
