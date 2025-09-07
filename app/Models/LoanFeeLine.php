<?php

namespace App\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use App\Enums\Loans\FeeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanFeeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id','date','type','amount','capitalize',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => DocumentCurrencyMoneyCast::class,
        'capitalize' => 'boolean',
        'type' => FeeType::class,
    ];

    public function loan(): BelongsTo { return $this->belongsTo(LoanAgreement::class, 'loan_id'); }
}

