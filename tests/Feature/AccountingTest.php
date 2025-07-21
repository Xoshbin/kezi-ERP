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
