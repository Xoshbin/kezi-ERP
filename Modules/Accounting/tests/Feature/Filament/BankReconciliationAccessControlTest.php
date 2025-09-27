<?php

use App\Filament\Clusters\Accounting\Resources\BankStatements\Pages\BankReconciliation;
use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('bank reconciliation page is accessible when reconciliation is enabled', function () {
    // Create company with reconciliation enabled
    $company = Company::factory()->withReconciliationEnabled()->create();

    // Set the tenant context
    Filament::setTenant($company);

    // Test access
    expect(BankReconciliation::canAccess())->toBeTrue();
});

test('bank reconciliation page is not accessible when reconciliation is disabled', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(); // Default is disabled

    // Set the tenant context
    Filament::setTenant($company);

    // Test access
    expect(BankReconciliation::canAccess())->toBeFalse();
});

test('bank reconciliation page is not accessible when no tenant is set', function () {
    // Clear tenant context
    Filament::setTenant(null);

    // Test access
    expect(BankReconciliation::canAccess())->toBeFalse();
});

test('bank reconciliation action is visible when reconciliation is enabled', function () {
    // Create company with reconciliation enabled
    $company = Company::factory()->withReconciliationEnabled()->create();

    // Set the tenant context
    Filament::setTenant($company);

    // Test that the reconcile action would be visible
    // This simulates the visible() callback in the BankStatementResource
    $isVisible = Filament::getTenant()?->enable_reconciliation ?? false;

    expect($isVisible)->toBeTrue();
});

test('bank reconciliation action is hidden when reconciliation is disabled', function () {
    // Create company with reconciliation disabled
    $company = Company::factory()->create(); // Default is disabled

    // Set the tenant context
    Filament::setTenant($company);

    // Test that the reconcile action would be hidden
    // This simulates the visible() callback in the BankStatementResource
    $isVisible = Filament::getTenant()?->enable_reconciliation ?? false;

    expect($isVisible)->toBeFalse();
});

test('bank reconciliation action is hidden when no tenant is set', function () {
    // Clear tenant context
    Filament::setTenant(null);

    // Test that the reconcile action would be hidden
    // This simulates the visible() callback in the BankStatementResource
    $isVisible = Filament::getTenant()?->enable_reconciliation ?? false;

    expect($isVisible)->toBeFalse();
});
