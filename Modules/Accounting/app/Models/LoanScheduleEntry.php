<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanScheduleEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'sequence', 'due_date', 'payment_amount', 'principal_component', 'interest_component', 'outstanding_balance_after',
        'is_accrual_posted', 'is_payment_posted', 'journal_entry_id_accrual', 'journal_entry_id_payment',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_amount' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'principal_component' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'interest_component' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'outstanding_balance_after' => \Modules\Foundation\Casts\DocumentCurrencyMoneyCast::class,
        'is_accrual_posted' => 'boolean',
        'is_payment_posted' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
