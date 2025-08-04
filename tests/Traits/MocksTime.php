<?php

namespace Tests\Traits;

use Carbon\Carbon;

trait MocksTime
{
    /**
     * This method is automatically called by Pest BEFORE each test in a file that `uses` this trait.
     * It ensures that time is always reset to the present, preventing tests from affecting each other.
     */
    protected function setUpMocksTime(): void
    {
        $this->returnToPresent();
    }

    /**
     * Travels to a date in the future, ensuring no accounting periods are locked by default.
     */
    public function travelToTheFuture(int $years = 1): void
    {
        Carbon::setTestNow(now()->addYears($years));
    }

    /**
     * Travels to a specific date.
     * Renamed from travelTo() to travelToDate() to avoid conflict with Laravel's built-in helper.
     */
    public function travelToDate(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date));
    }

    /**
     * Resets the mocked time back to the actual current time.
     */
    public function returnToPresent(): void
    {
        Carbon::setTestNow();
    }
}
