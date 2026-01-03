<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Accounting\Models\FiscalPeriod;

/**
 * Dispatched when a fiscal period is closed.
 *
 * Listeners can react to this event to update lock dates,
 * trigger notifications, or perform other side effects.
 */
final class FiscalPeriodClosed
{
    use Dispatchable;

    public function __construct(
        public readonly FiscalPeriod $fiscalPeriod,
    ) {}
}
