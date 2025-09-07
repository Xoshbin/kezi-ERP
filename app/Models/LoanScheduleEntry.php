<?php

namespace App\Models;

use App\Casts\DocumentCurrencyMoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanScheduleEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id','sequence','due_date','payment_amount','principal_component','interest_component','outstanding_balance_after',
        'is_accrual_posted','is_payment_posted','journal_entry_id_accrual','journal_entry_id_payment',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_amount' => DocumentCurrencyMoneyCast::class,
        'principal_component' => DocumentCurrencyMoneyCast::class,
        'interest_component' => DocumentCurrencyMoneyCast::class,
        'outstanding_balance_after' => DocumentCurrencyMoneyCast::class,
        'is_accrual_posted' => 'boolean',
        'is_payment_posted' => 'boolean',
    ];

    public function loan(): BelongsTo { return $this->belongsTo(LoanAgreement::class, 'loan_id'); }
}

