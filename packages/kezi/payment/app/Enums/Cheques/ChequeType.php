<?php

namespace Kezi\Payment\Enums\Cheques;

use Filament\Support\Contracts\HasLabel;

enum ChequeType: string implements HasLabel
{
    case Payable = 'payable';     // We write and give (Outgoing)
    case Receivable = 'receivable'; // We receive from customer (Incoming)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Payable => 'Payable (Outgoing)',
            self::Receivable => 'Receivable (Incoming)',
        };
    }
}
