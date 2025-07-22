<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    /** @use HasFactory<\Database\Factories\BankStatementFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'journal_id',
        'reference',
        'date',
        'starting_balance',
        'ending_balance',
    ];

    protected $casts = [
        'date' => 'date',
        'starting_balance' => MoneyCast::class,
        'ending_balance' => MoneyCast::class,
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
