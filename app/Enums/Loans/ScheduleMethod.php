<?php

namespace App\Enums\Loans;

enum ScheduleMethod: string
{
    case Annuity = 'annuity';
    case StraightLinePrincipal = 'straight_line_principal';
    case InterestOnly = 'interest_only';
    case Bullet = 'bullet';
    case Custom = 'custom';
}

