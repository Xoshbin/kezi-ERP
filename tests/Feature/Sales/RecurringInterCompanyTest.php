<?php

use App\Actions\Sales\CreateRecurringInterCompanyInvoiceAction;
use App\DataTransferObjects\Sales\CreateRecurringInvoiceTemplateDTO;
use App\DataTransferObjects\Sales\RecurringInvoiceLineDTO;
use App\Enums\RecurringInvoice\RecurringFrequency;
use App\Enums\RecurringInvoice\RecurringStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\RecurringInvoiceTemplate;
use App\Models\Tax;
use App\Models\User;
use App\Services\RecurringInterCompanyService;
use Brick\Money\Money;
use Carbon\Carbon;

beforeEach(function () {
    // Set up inter-company hierarchy for all tests
    setupInterCompanyHierarchyForRecurring();
});

test('recurring inter-company service creates template correctly', function () {
    // ARRANGE: Set up test data
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $user = User::factory()->create();

    $incomeAccount = Account::factory()->income()->create([
        'company_id' => $parentCompany->id,
    ]);

    $expenseAccount = Account::factory()->expense()->create([
        'company_id' => $childCompany->id,
    ]);

    $lines = [
        new RecurringInvoiceLineDTO(
            description: 'Management Fee',
            quantity: 1.0,
            unit_price: Money::ofMinor(500000, 'USD'), // 5000 USD in cents
        ),
    ];

    $dto = new CreateRecurringInvoiceTemplateDTO(
        company_id: $parentCompany->id,
        target_company_id: $childCompany->id,
        name: 'Monthly Management Fee',
        description: 'Monthly management services',
        frequency: RecurringFrequency::Monthly,
        start_date: Carbon::now(),
        end_date: null,
        day_of_month: 1,
        month_of_quarter: 1,
        currency_id: $parentCompany->currency_id,
        income_account_id: $incomeAccount->id,
        expense_account_id: $expenseAccount->id,
        tax_id: null,
        lines: $lines,
        created_by_user_id: $user->id,
    );

    // ACT: Create the template
    $service = app(RecurringInterCompanyService::class);
    $template = $service->createTemplate($dto);

    // ASSERT: Verify template was created correctly
    expect($template)->not->toBeNull();
    expect($template->company_id)->toBe($parentCompany->id);
    expect($template->target_company_id)->toBe($childCompany->id);
    expect($template->name)->toBe('Monthly Management Fee');
    expect($template->frequency)->toBe(RecurringFrequency::Monthly);
    expect($template->status)->toBe(RecurringStatus::Active);
    expect($template->template_data['lines'])->toHaveCount(1);
    expect($template->template_data['lines'][0]['description'])->toBe('Management Fee');
    expect($template->template_data['lines'][0]['unit_price']['amount'])->toBe(500000); // 5000 USD in cents
});

test('recurring service validates inter-company relationship', function () {
    // ARRANGE: Set up companies without partner relationship
    $company1 = Company::factory()->create(['name' => 'Company1']);
    $company2 = Company::factory()->create(['name' => 'Company2']);
    $user = User::factory()->create();

    $lines = [
        new RecurringInvoiceLineDTO(
            description: 'Test Service',
            quantity: 1.0,
            unit_price: Money::of(1000, 'USD'),
        ),
    ];

    $dto = new CreateRecurringInvoiceTemplateDTO(
        company_id: $company1->id,
        target_company_id: $company2->id,
        name: 'Test Template',
        description: null,
        frequency: RecurringFrequency::Monthly,
        start_date: Carbon::now(),
        end_date: null,
        day_of_month: 1,
        month_of_quarter: 1,
        currency_id: $company1->currency_id,
        income_account_id: Account::factory()->create(['company_id' => $company1->id])->id,
        expense_account_id: Account::factory()->create(['company_id' => $company2->id])->id,
        tax_id: null,
        lines: $lines,
        created_by_user_id: $user->id,
    );

    // ACT & ASSERT: Should throw validation exception
    $service = app(RecurringInterCompanyService::class);

    expect(fn() => $service->createTemplate($dto))
        ->toThrow(\InvalidArgumentException::class, 'No partner relationship found');
});

test('recurring service calculates next run date correctly', function () {
    // ARRANGE: Create template with specific start date
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $user = User::factory()->create();

    $startDate = Carbon::create(2024, 1, 15); // January 15th

    $template = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'frequency' => RecurringFrequency::Monthly,
        'start_date' => $startDate,
        'day_of_month' => 1,
        'next_run_date' => $startDate->copy()->day(1)->addMonth(), // Should be Feb 1st
    ]);

    // ACT: Calculate next run date
    $nextRunDate = $template->calculateNextRunDate();

    // ASSERT: Should be March 1st (one month after Feb 1st)
    expect($nextRunDate->format('Y-m-d'))->toBe('2024-03-01');
});

test('recurring service gets due templates correctly', function () {
    // ARRANGE: Create templates with different due dates
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    // Template that is due
    $dueTemplate = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'status' => RecurringStatus::Active,
        'is_active' => true,
        'next_run_date' => now()->subDay(), // Yesterday
    ]);

    // Template that is not due yet
    $notDueTemplate = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'status' => RecurringStatus::Active,
        'is_active' => true,
        'next_run_date' => now()->addDay(), // Tomorrow
    ]);

    // Paused template
    $pausedTemplate = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'status' => RecurringStatus::Paused,
        'is_active' => true,
        'next_run_date' => now()->subDay(),
    ]);

    // ACT: Get due templates
    $service = app(RecurringInterCompanyService::class);
    $dueTemplates = $service->getDueTemplates();

    // ASSERT: Only the due template should be returned
    expect($dueTemplates)->toHaveCount(1);
    expect($dueTemplates->first()->id)->toBe($dueTemplate->id);
});

test('create recurring inter-company invoice action generates invoice and vendor bill', function () {
    // ARRANGE: Set up template and DTO
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();
    $user = User::factory()->create();

    $template = RecurringInvoiceTemplate::factory()->due()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
    ]);

    $service = app(RecurringInterCompanyService::class);
    $recurringInvoiceDTO = $service->generateFromTemplate($template, $user);

    // ACT: Execute the action
    $action = app(CreateRecurringInterCompanyInvoiceAction::class);
    $result = $action->execute($recurringInvoiceDTO, $user);

    // ASSERT: Verify invoice and vendor bill were created
    expect($result->success)->toBeTrue();
    expect($result->invoice)->not->toBeNull();
    expect($result->vendorBill)->not->toBeNull();
    expect($result->invoice->company_id)->toBe($parentCompany->id);
    expect($result->vendorBill->company_id)->toBe($childCompany->id);
    expect($result->invoice->recurring_template_id)->toBe($template->id);
    expect($result->vendorBill->recurring_template_id)->toBe($template->id);

    // Verify reference patterns
    expect($result->invoice->reference)->toContain('IC-RECURRING');
    expect($result->vendorBill->bill_reference)->toContain('IC-RECURRING-BILL');
});

test('recurring template updates after generation', function () {
    // ARRANGE: Create template
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    $originalNextRun = now()->subDay();
    $template = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'frequency' => RecurringFrequency::Monthly,
        'next_run_date' => $originalNextRun,
        'generation_count' => 0,
        'last_generated_at' => null,
    ]);

    // ACT: Update after generation
    $service = app(RecurringInterCompanyService::class);
    $service->updateTemplateAfterGeneration($template);

    // ASSERT: Verify template was updated
    $template->refresh();
    expect($template->generation_count)->toBe(1);
    expect($template->last_generated_at)->not->toBeNull();
    expect($template->next_run_date)->toBeGreaterThan($originalNextRun);
});

test('recurring template can be paused and resumed', function () {
    // ARRANGE: Create active template
    $template = RecurringInvoiceTemplate::factory()->create([
        'status' => RecurringStatus::Active,
    ]);

    $service = app(RecurringInterCompanyService::class);

    // ACT: Pause template
    $service->pauseTemplate($template);

    // ASSERT: Template is paused
    $template->refresh();
    expect($template->status)->toBe(RecurringStatus::Paused);

    // ACT: Resume template
    $service->resumeTemplate($template);

    // ASSERT: Template is active again
    $template->refresh();
    expect($template->status)->toBe(RecurringStatus::Active);
});

test('recurring template with end date completes correctly', function () {
    // ARRANGE: Create template with end date in the past
    $parentCompany = Company::where('name', 'ParentCo')->first();
    $childCompany = Company::where('name', 'ChildCo')->first();

    $template = RecurringInvoiceTemplate::factory()->create([
        'company_id' => $parentCompany->id,
        'target_company_id' => $childCompany->id,
        'end_date' => now()->subDay(),
        'next_run_date' => now()->addDay(), // Next run is after end date
    ]);

    // ACT: Check if should complete
    $shouldComplete = $template->shouldComplete();

    // ASSERT: Template should be marked for completion
    expect($shouldComplete)->toBeTrue();
});

// Helper method to set up inter-company hierarchy
function setupInterCompanyHierarchyForRecurring(): void
{
    $parentCompany = Company::factory()->create(['name' => 'ParentCo']);
    $childCompany = Company::factory()->create([
        'name' => 'ChildCo',
        'parent_company_id' => $parentCompany->id,
    ]);

    // Create partner relationships
    Partner::factory()->create([
        'company_id' => $parentCompany->id,
        'name' => 'ChildCo Partner',
        'linked_company_id' => $childCompany->id,
    ]);

    Partner::factory()->create([
        'company_id' => $childCompany->id,
        'name' => 'ParentCo Partner',
        'linked_company_id' => $parentCompany->id,
    ]);
}
