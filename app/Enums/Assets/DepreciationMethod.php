<?php

namespace App\Enums\Assets;

enum DepreciationMethod: string
{
    case StraightLine = 'straight_line';
    case Declining = 'declining';
}
