<?php

namespace App\Enums\Loans;

enum FeeType: string
{
    case Origination = 'origination';
    case Service = 'service';
    case Legal = 'legal';
    case Penalty = 'penalty';
    case Other = 'other';
}
