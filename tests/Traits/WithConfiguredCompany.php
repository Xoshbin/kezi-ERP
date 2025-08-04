<?php

namespace Tests\Traits;

use App\Models\User;
use Tests\Builders\CompanyBuilder;

trait WithConfiguredCompany
{
    protected function setupWithConfiguredCompany(): void
    {
        $this->company = CompanyBuilder::new()
            ->withDefaultAccounts()
            ->withDefaultJournals()
            ->create();

        $this->user = User::factory()->for($this->company)->create();
        $this->actingAs($this->user);
    }
}
