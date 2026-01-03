<?php

namespace Modules\Purchase\Tests\Feature\Filament;

use Livewire\Livewire;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\CreateRequestForQuotation;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\RequestForQuotations\Pages\ListRequestForQuotations;

it('can render list page', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListRequestForQuotations::class)
        ->assertSuccessful();
});

it('can render create page', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CreateRequestForQuotation::class)
        ->assertSuccessful();
});

it('can create an RFQ', function () {
    $user = \App\Models\User::factory()->create();
    $company = \App\Models\Company::factory()->create();
    $user->update(['current_company_id' => $company->id]);
    $vendor = Partner::factory()->create(['company_id' => $company->id, 'is_vendor' => true]);
    $currency = Currency::factory()->create(['code' => 'USD']);

    $this->actingAs($user);

    Livewire::test(CreateRequestForQuotation::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'rfq_date' => now(),
            'currency_id' => $currency->id,
            'exchange_rate' => 1,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('request_for_quotations', [
        'vendor_id' => $vendor->id,
        'company_id' => $company->id,
    ]);
});
