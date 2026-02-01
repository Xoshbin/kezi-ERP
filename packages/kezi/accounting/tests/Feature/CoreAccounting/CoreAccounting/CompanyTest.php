<?php

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\Models\Account;
use Kezi\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

test('a company with existing financial records cannot be deleted', function () {
    // Arrange: Create a dependent financial record.
    Account::factory()->for($this->company)->create();

    // Assert: Expect the exact message thrown by the CompanyObserver.
    expect(fn () => $this->company->delete())
        ->toThrow(\Kezi\Foundation\Exceptions\DeletionNotAllowedException::class, 'Cannot delete a company with associated financial records.'); // <-- Corrected Message

    // Verify: Double-check that the company was NOT removed from the database.
    $this->assertModelExists($this->company);
});

test('a user is correctly related to their company for accounting contexts', function () {
    // Verifies the structural integrity crucial for multi-company accounting.
    expect($this->user->companies->contains($this->company))->toBeTrue();
});

test('duplicate tax ID for a company in the same fiscal country is prevented', function () {
    // Arrange: Create the first company that sets the baseline for the unique rule.
    Company::factory()->create(['tax_id' => 'VAT123', 'fiscal_country' => 'IQ', 'currency_id' => $this->company->currency_id]);

    // Arrange: Prepare the data for the second, duplicate company.
    $duplicateCompanyData = [
        'name' => 'Duplicate Tax ID Company',
        'tax_id' => 'VAT123', // Same tax_id
        'fiscal_country' => 'IQ', // Same country
        'currency_id' => $this->company->currency_id,
    ];

    // Arrange: Instantiate the service that contains our business logic.
    $companyService = app(\Kezi\Foundation\Services\CompanyService::class);

    // Assert: We expect that calling the service's create method with duplicate data
    // will fail validation and throw Laravel's standard ValidationException.
    expect(fn () => $companyService->create($duplicateCompanyData))
        ->toThrow(ValidationException::class);
});

test('creating a currency with an existing code is prevented', function () {
    // Arrange: Create the initial currency.
    Currency::factory()->createSafely(['code' => 'XYZ']); // Use a unique code to avoid conflicts.

    // Arrange: Prepare the data for the duplicate currency.
    $duplicateData = [
        'code' => 'XYZ', // The duplicate code
        'name' => 'Test Currency Duplicate',
        'symbol' => 'T',
        'exchange_rate' => 1.0,
    ];

    // Arrange: Instantiate the service that holds the creation logic.
    $currencyService = app(\Kezi\Foundation\Services\CurrencyService::class);

    // Assert: Expect the service to throw a ValidationException when trying to create
    // the duplicate record, proving the business rule is enforced.
    expect(fn () => $currencyService->create($duplicateData))
        ->toThrow(ValidationException::class);
});
