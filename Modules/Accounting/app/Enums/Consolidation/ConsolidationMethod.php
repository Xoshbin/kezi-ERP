<?php

namespace Modules\Accounting\Enums\Consolidation;

use Filament\Support\Contracts\HasLabel;

enum ConsolidationMethod: string implements HasLabel
{
    case Full = 'full';
    case Proportional = 'proportional';
    case Equity = 'equity';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Full => 'Full Consolidation',
            self::Proportional => 'Proportional Consolidation',
            self::Equity => 'Equity Method',
        };
    }
}
