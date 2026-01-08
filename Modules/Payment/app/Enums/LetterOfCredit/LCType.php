<?php

namespace Modules\Payment\Enums\LetterOfCredit;

use Filament\Support\Contracts\HasLabel;

enum LCType: string implements HasLabel
{
    case Import = 'import';     // For importing goods
    case Export = 'export';     // For export transactions
    case Standby = 'standby';   // Guarantee type LC

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Import => 'Import LC',
            self::Export => 'Export LC',
            self::Standby => 'Standby LC',
        };
    }
}
