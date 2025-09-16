<?php

use App\Enums\CustomFields\CustomFieldType;
use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use App\Models\CustomFieldDefinition;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);

    // Create a custom field definition for testing
    $this->definition = CustomFieldDefinition::factory()->create([
        'model_type' => Partner::class,
        'field_definitions' => [
            [
                'key' => 'industry',
                'label' => ['en' => 'Industry', 'ar' => 'الصناعة'],
                'type' => CustomFieldType::Text->value,
                'required' => false,
                'order' => 1,
            ],
            [
                'key' => 'priority',
                'label' => ['en' => 'Priority'],
                'type' => CustomFieldType::Select->value,
                'required' => true,
                'order' => 2,
                'options' => [
                    ['value' => 'high', 'label' => ['en' => 'High']],
                    ['value' => 'medium', 'label' => ['en' => 'Medium']],
                    ['value' => 'low', 'label' => ['en' => 'Low']],
                ],
            ],
        ],
    ]);
});

it('can render custom field definition list page', function () {
    $this->get(CustomFieldDefinitionResource::getUrl('index'))->assertSuccessful();
});

it('can render custom field definition create page', function () {
    $this->get(CustomFieldDefinitionResource::getUrl('create'))->assertSuccessful();
});

it('can create custom field definition through filament', function () {
    livewire(\App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\CreateCustomFieldDefinition::class)
        ->fillForm([
            'model_type' => Product::class,
            'name' => ['en' => 'Test Custom Fields'],
            'description' => ['en' => 'Test description'],
            'field_definitions' => [
                [
                    'key' => 'test_field',
                    'label' => ['en' => 'Test Field'],
                    'type' => CustomFieldType::Text->value,
                    'required' => false,
                    'order' => 1,
                ],
            ],
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('can edit custom field definition through filament', function () {
    livewire(\App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Pages\EditCustomFieldDefinition::class, [
        'record' => $this->definition->getRouteKey(),
    ])
        ->fillForm([
            'name' => ['en' => 'Updated Custom Fields'],
            'description' => ['en' => 'Updated description'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('can create partner with custom fields', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner::class)
        ->fillForm([
            'name' => 'Test Partner',
            'type' => \App\Enums\Partners\PartnerType::Customer->value,
            'email' => 'test@example.com',
            'custom_fields.industry' => 'Technology',
            'custom_fields.priority' => 'high',
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('can edit partner with custom fields', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner::class, [
        'record' => $partner->getRouteKey(),
    ])
        ->fillForm([
            'custom_fields.industry' => 'Healthcare',
            'custom_fields.priority' => 'medium',
        ])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('validates required custom fields in filament', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner::class)
        ->fillForm([
            'name' => 'Test Partner',
            'type' => \App\Enums\Partners\PartnerType::Customer->value,
            'email' => 'test@example.com',
            'custom_fields.industry' => 'Technology',
            // Missing required 'priority' field
        ])
        ->call('create')
        ->assertHasFormErrors(['custom_fields.priority']);
});

it('validates select field options in filament', function () {
    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner::class)
        ->fillForm([
            'name' => 'Test Partner',
            'type' => \App\Enums\Partners\PartnerType::Customer->value,
            'email' => 'test@example.com',
            'custom_fields.industry' => 'Technology',
            'custom_fields.priority' => 'invalid_option', // Invalid option
        ])
        ->call('create')
        ->assertHasFormErrors(['custom_fields.priority']);
});

it('custom fields component renders correctly', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner::class, [
        'record' => $partner->getRouteKey(),
    ])
        ->assertSeeHtml('Industry')
        ->assertSeeHtml('Priority');
});

it('custom fields section is collapsible', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner::class, [
        'record' => $partner->getRouteKey(),
    ])
        ->assertSeeHtml('Custom Fields');
});

it('handles translatable custom field values', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner::class)
        ->fillForm([
            'name' => 'Test Partner',
            'type' => \App\Enums\Partners\PartnerType::Customer->value,
            'email' => 'test@example.com',
            'custom_fields.industry' => 'Technology',
            'custom_fields.priority' => 'high',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $createdPartner = Partner::where('name', 'Test Partner')->first();
    expect($createdPartner->getCustomFieldValue('industry'))->toBe('Technology');
    expect($createdPartner->getCustomFieldValue('priority'))->toBe('high');
});

it('custom fields are not shown when no definition exists', function () {
    // Delete the definition
    $this->definition->delete();

    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner::class, [
        'record' => $partner->getRouteKey(),
    ])
        ->assertDontSeeHtml('Custom Fields');
});
