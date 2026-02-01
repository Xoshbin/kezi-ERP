<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use Jmeryar\Foundation\Database\Seeders\PartnerCustomFieldSeeder;
use Jmeryar\Foundation\Enums\Partners\PartnerType;
use Jmeryar\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;
use Xoshbin\CustomFields\Filament\Tables\Components\CustomFieldTableColumns;
use Xoshbin\CustomFields\Models\CustomFieldDefinition;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Run the seeder to create the custom field definition
    $this->seed(PartnerCustomFieldSeeder::class);
});

it('allows creating partners with the company custom field through filament', function () {
    livewire(CreatePartner::class)
        ->fillForm([
            'name' => 'Test Company Partner',
            'type' => PartnerType::Customer->value,
            'email' => 'test@company.com',
            'custom_fields.company' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the partner was created with the custom field value
    $partner = Partner::where('name', 'Test Company Partner')->first();
    expect($partner)->not->toBeNull();

    $customFieldValue = $partner->getCustomFieldValue('company');
    expect($customFieldValue)->toBeTrue();
});

it('allows creating partners without the company field checked', function () {
    livewire(CreatePartner::class)
        ->fillForm([
            'name' => 'Test Individual Partner',
            'type' => PartnerType::Customer->value,
            'email' => 'test@individual.com',
            'custom_fields.company' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the partner was created with the custom field value
    $partner = Partner::where('name', 'Test Individual Partner')->first();
    expect($partner)->not->toBeNull();

    $customFieldValue = $partner->getCustomFieldValue('company');
    expect($customFieldValue)->toBeFalse();
});

it('generates table columns for the company field since show_in_table is true', function () {
    $columns = CustomFieldTableColumns::make(Partner::class);

    expect($columns)->toHaveCount(1);
    expect($columns[0]->getName())->toBe('custom_fields.company');
});

it('includes company field in sortable columns but not searchable (boolean fields are not searchable)', function () {
    $searchableColumns = CustomFieldTableColumns::getSearchableColumns(Partner::class);
    $sortableColumns = CustomFieldTableColumns::getSortableColumns(Partner::class);

    // Boolean fields are not searchable (only text-based fields are)
    expect($searchableColumns)->not->toContain('custom_fields.company');

    // But boolean fields are sortable
    expect($sortableColumns)->toContain('custom_fields.company');
});

it('works with the existing custom field system', function () {
    // Verify the definition exists and is properly configured
    $definition = CustomFieldDefinition::where('model_type', Partner::class)->first();
    expect($definition)->not->toBeNull();
    expect($definition->is_active)->toBeTrue();

    // Create a partner and set custom field values
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Integration Test Partner',
    ]);

    // Set the custom field value
    $partner->setCustomFieldValue('company', true);

    // Verify it was saved correctly
    expect($partner->getCustomFieldValue('company'))->toBeTrue();

    // Verify it appears in the custom field values collection
    $customFieldValues = $partner->getCustomFieldValues();
    expect($customFieldValues)->toHaveKey('company');
    expect($customFieldValues['company'])->toBeTrue();
});
