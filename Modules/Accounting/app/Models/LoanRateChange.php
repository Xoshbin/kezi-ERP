<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRateChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'effective_date', 'annual_rate',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'annual_rate' => 'float',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(LoanAgreement::class, 'loan_id');
    }
}
