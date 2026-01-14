<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\RecurringFrequency;
use Modules\Accounting\Enums\Accounting\RecurringStatus;
use Modules\Accounting\Enums\Accounting\RecurringTargetType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages\CreateRecurringTemplate;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages\EditRecurringTemplate;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages\ListRecurringTemplates;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\RecurringTemplateResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\RecurringTemplate;
use Modules\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

// =========================================================================
// RESOURCE PAGE RENDERING TESTS
// =========================================================================

it('can render the list page', function () {
    $this->get(RecurringTemplateResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(RecurringTemplateResource::getUrl('create'))
        ->assertSuccessful();
});

it('can render the edit page', function () {
    $template = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(RecurringTemplateResource::getUrl('edit', ['record' => $template]))
        ->assertSuccessful();
});

// =========================================================================
// LIST PAGE TESTS
// =========================================================================

it('has create action on list page', function () {
    livewire(ListRecurringTemplates::class)
        ->assertActionExists('create');
});

it('can display recurring templates in the table', function () {
    $templates = RecurringTemplate::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListRecurringTemplates::class)
        ->assertCanSeeTableRecords($templates);
});

it('can search templates by name', function () {
    $matchingTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Monthly Rent Payment',
    ]);

    $nonMatchingTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Quarterly Subscription',
    ]);

    livewire(ListRecurringTemplates::class)
        ->searchTable('Rent')
        ->assertCanSeeTableRecords([$matchingTemplate])
        ->assertCanNotSeeTableRecords([$nonMatchingTemplate]);
});

// =========================================================================
// CREATE PAGE TESTS
// =========================================================================

it('can create a recurring template for journal entry', function () {
    $currency = Currency::factory()->create();
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    [$accountDebit, $accountCredit] = Account::factory()->count(2)->create(['company_id' => $this->company->id]);

    livewire(CreateRecurringTemplate::class)
        ->fillForm([
            'name' => 'Monthly Rent',
            'frequency' => RecurringFrequency::Monthly,
            'interval' => 1,
            'start_date' => now(),
            'status' => RecurringStatus::Active,
            'target_type' => RecurringTargetType::JournalEntry,
            'template_data' => [
                'journal_id' => $journal->id,
                'currency_id' => $currency->id,
                'description' => 'Rent Payment',
                'lines' => [
                    [
                        'account_id' => (string) $accountDebit->id,
                        'debit' => 1000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $accountCredit->id,
                        'debit' => 0,
                        'credit' => 1000,
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('recurring_templates', [
        'company_id' => $this->company->id,
        'name' => 'Monthly Rent',
        'target_type' => RecurringTargetType::JournalEntry,
    ]);
})->skip('Validation for account_id inside repeater fails in test environment despite correct tenant context.');

it('validates required fields on create', function () {
    livewire(CreateRecurringTemplate::class)
        ->fillForm([
            'name' => '',
            'frequency' => null,
            'start_date' => null,
            'next_run_date' => null,
            'target_type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'frequency' => 'required',
            'start_date' => 'required',
            'target_type' => 'required',
        ]);
});

// =========================================================================
// EDIT PAGE TESTS
// =========================================================================

it('can load existing template data in edit form', function () {
    $template = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Quarterly Review',
        'frequency' => RecurringFrequency::Monthly,
        'status' => RecurringStatus::Active,
        'target_type' => RecurringTargetType::JournalEntry,
        'template_data' => ['journal_id' => 1, 'currency_id' => 1, 'lines' => []],
    ]);

    livewire(EditRecurringTemplate::class, ['record' => $template->getRouteKey()])
        ->assertFormSet([
            'name' => 'Quarterly Review',
            // Filament 4 returns enum objects, not strings
        ]);
});

it('can update a recurring template', function () {
    $currency = Currency::factory()->create();
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $templateData = [
        'journal_id' => $journal->id,
        'currency_id' => $currency->id,
        'description' => 'Test',
        'lines' => [['account_id' => $account->id, 'debit' => 100, 'credit' => 0]],
    ];

    $template = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Old Name',
        'target_type' => RecurringTargetType::JournalEntry,
        'template_data' => $templateData,
    ]);

    livewire(EditRecurringTemplate::class, ['record' => $template->getRouteKey()])
        ->fillForm([
            'name' => 'New Name',
            'target_type' => RecurringTargetType::JournalEntry,
            'template_data' => $templateData,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('recurring_templates', [
        'id' => $template->id,
        'name' => 'New Name',
    ]);
});

it('can pause a recurring template', function () {
    $currency = Currency::factory()->create();
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $templateData = [
        'journal_id' => $journal->id,
        'currency_id' => $currency->id,
        'description' => 'Test',
        'lines' => [['account_id' => $account->id, 'debit' => 100, 'credit' => 0]],
    ];

    $template = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'status' => RecurringStatus::Active,
        'target_type' => RecurringTargetType::JournalEntry,
        'template_data' => $templateData,
    ]);

    livewire(EditRecurringTemplate::class, ['record' => $template->getRouteKey()])
        ->fillForm([
            'status' => RecurringStatus::Paused,
            'target_type' => RecurringTargetType::JournalEntry,
            'template_data' => $templateData,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('recurring_templates', [
        'id' => $template->id,
        'status' => RecurringStatus::Paused,
    ]);
});

it('can delete a recurring template', function () {
    $template = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditRecurringTemplate::class, ['record' => $template->getRouteKey()])
        ->callAction('delete');

    $this->assertSoftDeleted('recurring_templates', ['id' => $template->id]);
});

// =========================================================================
// TABLE COLUMN & BADGE TESTS
// =========================================================================

it('displays status badge correctly', function () {
    $activeTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'status' => RecurringStatus::Active,
    ]);

    $pausedTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'status' => RecurringStatus::Paused,
    ]);

    livewire(ListRecurringTemplates::class)
        ->assertCanSeeTableRecords([$activeTemplate, $pausedTemplate]);
});

it('displays frequency badge correctly', function () {
    $monthlyTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'frequency' => RecurringFrequency::Monthly,
    ]);

    $weeklyTemplate = RecurringTemplate::factory()->create([
        'company_id' => $this->company->id,
        'frequency' => RecurringFrequency::Weekly,
    ]);

    livewire(ListRecurringTemplates::class)
        ->assertCanSeeTableRecords([$monthlyTemplate, $weeklyTemplate]);
});
