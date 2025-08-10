<?php

use App\Filament\Clusters\Settings\Resources\PdfSettingsResource;
use App\Models\User;
use App\Models\Company;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create([
        'pdf_template' => 'classic',
        'pdf_settings' => [
            'font_size' => '12',
            'margin_top' => '20',
            'show_company_logo' => 'true',
        ],
    ]);
    $this->user->update(['company_id' => $this->company->id]);
});

test('user can view pdf settings list page', function () {
    // Action & Assert
    $this->actingAs($this->user)
        ->get(PdfSettingsResource::getUrl('index'))
        ->assertSuccessful();
});

test('user can view pdf settings edit page', function () {
    // Action & Assert
    $this->actingAs($this->user)
        ->get(PdfSettingsResource::getUrl('edit', ['record' => $this->company]))
        ->assertSuccessful();
});

test('user can update pdf template setting', function () {
    // Action
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_template' => 'modern',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert
    expect($this->company->fresh()->pdf_template)->toBe('modern');
});

test('user can update pdf logo', function () {
    // Arrange
    $logoPath = 'company-logos/test-logo.png';

    // Action
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_logo_path' => [$logoPath], // File uploads expect arrays
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert
    expect($this->company->fresh()->pdf_logo_path)->toBe($logoPath);
});

test('user can update custom pdf settings', function () {
    // Arrange
    $customSettings = [
        'font_size' => '14',
        'margin_top' => '25',
        'margin_bottom' => '25',
        'show_company_logo' => 'false',
        'custom_footer_text' => 'Thank you for your business!',
    ];

    // Action
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_settings' => $customSettings,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert
    $updatedCompany = $this->company->fresh();
    expect($updatedCompany->pdf_settings)->toMatchArray($customSettings);
});

test('pdf template field is required', function () {
    // Action & Assert
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_template' => null,
        ])
        ->call('save')
        ->assertHasFormErrors(['pdf_template' => 'required']);
});

test('pdf template must be valid option', function () {
    // Action & Assert
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_template' => 'invalid-template',
        ])
        ->call('save')
        ->assertHasFormErrors(['pdf_template']);
});

test('user can only access their own company pdf settings', function () {
    // Arrange
    $otherCompany = Company::factory()->create();

    // Action & Assert
    $this->actingAs($this->user)
        ->get(PdfSettingsResource::getUrl('edit', ['record' => $otherCompany]))
        ->assertStatus(404); // Should not find the record due to query scope
});

test('pdf settings resource shows correct navigation label', function () {
    // Assert
    expect(PdfSettingsResource::getNavigationLabel())->toBe(__('PDF Settings'));
    expect(PdfSettingsResource::getModelLabel())->toBe(__('PDF Settings'));
    expect(PdfSettingsResource::getPluralModelLabel())->toBe(__('PDF Settings'));
});

test('pdf settings resource cannot create new records', function () {
    // Assert
    expect(PdfSettingsResource::canCreate())->toBeFalse();
});

test('pdf settings resource cannot delete records', function () {
    // Assert
    expect(PdfSettingsResource::canDelete($this->company))->toBeFalse();
});

test('pdf settings table shows company information', function () {
    // Action
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\ListPdfSettings::class)
        ->assertCanSeeTableRecords([$this->company])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('pdf_template')
        ->assertTableColumnExists('pdf_logo_path')
        ->assertTableColumnExists('updated_at');
});

test('pdf settings table filters by template', function () {
    // Arrange
    $modernCompany = Company::factory()->create([
        'pdf_template' => 'modern',
    ]);
    $this->user->update(['company_id' => $modernCompany->id]);

    // Action & Assert
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\ListPdfSettings::class)
        ->filterTable('pdf_template', 'modern')
        ->assertCanSeeTableRecords([$modernCompany]);
});

test('edit page shows preview pdf action', function () {
    // Action
    $component = Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ]);

    // Assert - Check that the preview action exists
    $component->assertActionExists('preview_pdf');
});

test('pdf settings form has all required sections', function () {
    // Action
    $component = Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ]);

    // Assert - Check that form components exist
    $component->assertFormFieldExists('pdf_template');
    $component->assertFormFieldExists('pdf_logo_path');
    $component->assertFormFieldExists('pdf_settings');
});

test('pdf settings saves successfully with notification', function () {
    // Action
    Livewire::actingAs($this->user)
        ->test(PdfSettingsResource\Pages\EditPdfSettings::class, [
            'record' => $this->company->getRouteKey(),
        ])
        ->fillForm([
            'pdf_template' => 'minimal',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified(); // Check that a notification was sent
});
