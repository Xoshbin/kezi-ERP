<?php

namespace Tests\Traits;

use Carbon\Carbon;

trait WithUnlockedPeriod
{
    public function setupWithUnlockedPeriod(): void
    {
        Carbon::setTestNow(now()->addYear());
    }

    public function tearDownWithUnlockedPeriod(): void
    {
        Carbon::setTestNow();
    }
}
