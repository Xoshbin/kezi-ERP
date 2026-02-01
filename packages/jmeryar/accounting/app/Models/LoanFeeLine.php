<?php

namespace Jmeryar\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Jmeryar\Accounting\Enums\Loans\FeeType;

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
        'amount' => \Jmeryar\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'capitalize' => 'boolean',
        'type' => FeeType::class,
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
