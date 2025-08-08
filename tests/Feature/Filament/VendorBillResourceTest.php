<?php

use App\Filament\Resources\VendorBillResource;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\Partner;
use App\Enums\Purchases\VendorBillStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(VendorBillResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(VendorBillResource::getUrl('create'))->assertSuccessful();
});

it('can create a vendor bill', function () {
    // To be implemented
});

it('can validate input on create', function () {
    // To be implemented
});

it('can render the edit page', function () {
    // To be implemented
});

it('can edit a vendor bill', function () {
    // To be implemented
});

it('can confirm a vendor bill', function () {
    // To be implemented
});

it('can reset a vendor bill to draft', function () {
    // To be implemented
});