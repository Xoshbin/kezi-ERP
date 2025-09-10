<?php

namespace App\Enums\Loans;

enum LoanType: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';
}
