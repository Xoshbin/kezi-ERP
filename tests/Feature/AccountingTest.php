<?php

use App\Models\AnalyticAccount;
use App\Models\Company;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\LockDate;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\AdjustmentDocument;
use App\Services\CompanyService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\DeletionNotAllowedException; // A custom exception you should create
use Illuminate\Validation\ValidationException; // The exception thrown by Laravel's validator

uses(RefreshDatabase::class);

test('a company with existing financial records cannot be deleted', function () {
    // Arrange: Create a company and a dependent financial record.
    $company = Company::factory()->create();
    Account::factory()->for($company)->create();

    // Assert: Expect that our business logic will throw a specific,
    // custom exception when this rule is violated.
    // This is much cleaner than checking for an HTTP status code.
    expect(fn() => $company->delete())
        ->toThrow(DeletionNotAllowedException::class, 'Cannot delete company with associated financial records.');

    // Act & Assert: Double-check that the company was NOT removed from the database.
    // This confirms the deletion was truly prevented.
    $this->assertModelExists($company);
});

test('a user is correctly related to their company for accounting contexts', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    // Verifies the structural integrity crucial for multi-company accounting.
    expect($user->company->id)->toBe($company->id);
});
test('duplicate tax ID for a company in the same fiscal country is prevented', function () {
    // Arrange: Create the first company that sets the baseline for the unique rule.
    Company::factory()->create(['tax_id' => 'VAT123', 'fiscal_country' => 'IQ']);

    // Arrange: Prepare the data for the second, duplicate company.
    $duplicateCompanyData = [
        'name' => 'Duplicate Tax ID Company',
        'tax_id' => 'VAT123', // Same tax_id
        'fiscal_country' => 'IQ', // Same country
        'currency_id' => Currency::factory()->create()->id,
    ];

    // Arrange: Instantiate the service that contains our business logic.
    $companyService = new CompanyService();

    // Assert: We expect that calling the service's create method with duplicate data
    // will fail validation and throw Laravel's standard ValidationException.
    expect(fn() => $companyService->create($duplicateCompanyData))
        ->toThrow(ValidationException::class);
});
