<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    /** @use HasFactory<\Database\Factories\BankStatementLineFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'date',
        'description',
        'partner_name',
        'amount',
        'is_reconciled',
        'payment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => MoneyCast::class,
        'is_reconciled' => 'boolean'
    ];
    public function bankStatement()
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
