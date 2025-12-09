<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Enums\Loans\FeeType;
use Modules\Accounting\Models\LoanAgreement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Foundation\Casts\DocumentCurrencyMoneyCast;

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
