<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can load filament panel with ai helper plugin registered', function () {
    // Check if the AI Helper plugin is properly registered
    $plugins = filament('jmeryar')->getPlugins();

    $aiHelperPlugin = collect($plugins)->first(function ($plugin) {
        return $plugin instanceof \AccounTech\FilamentAiHelper\FilamentAiHelperPlugin;
    });

    expect($aiHelperPlugin)->not->toBeNull();
});

it('has ai helper configuration loaded correctly', function () {
    // Check if configuration is loaded
    expect(config('filament-ai-helper'))->not->toBeNull();
    expect(config('filament-ai-helper.assistant.context_prompts'))->toHaveKey('invoice');
    expect(config('filament-ai-helper.assistant.context_prompts'))->toHaveKey('journalentry');
    expect(config('filament-ai-helper.assistant.context_prompts'))->toHaveKey('vendorbill');
});

it('can access invoice edit page with ai helper trait', function () {
    // Create test data (user is already created and authenticated in setupWithConfiguredCompany)
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $partner->id,
    ]);

    // Test that the page loads without errors
    $this->get(route('filament.jmeryar.resources.invoices.edit', [
        'tenant' => $this->company,
        'record' => $invoice
    ]))
    ->assertSuccessful();
});

it('can access journal entry edit page with ai helper trait', function () {
    // Create a journal entry using your existing factory or service
    $journalEntry = \App\Models\JournalEntry::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Test that the page loads without errors
    $this->get(route('filament.jmeryar.resources.journal-entries.edit', [
        'tenant' => $this->company,
        'record' => $journalEntry
    ]))
    ->assertSuccessful();
});

it('can access vendor bill edit page with ai helper trait', function () {
    // Create test data
    $user = User::factory()->create();
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $vendorBill = \App\Models\VendorBill::factory()->create([
        'company_id' => $this->company->id,
        'vendor_id' => $partner->id,
    ]);

    // Test that the page loads without errors
    $this->actingAs($user)
        ->get(route('filament.jmeryar.resources.vendor-bills.edit', [
            'tenant' => $this->company,
            'record' => $vendorBill
        ]))
        ->assertSuccessful();
});

it('can access partner edit page with ai helper trait', function () {
    // Create test data
    $user = User::factory()->create();
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);

    // Test that the page loads without errors
    $this->actingAs($user)
        ->get(route('filament.jmeryar.resources.partners.edit', [
            'tenant' => $this->company,
            'record' => $partner
        ]))
        ->assertSuccessful();
});

it('has proper ai helper context prompts for accounting models', function () {
    $contextPrompts = config('filament-ai-helper.assistant.context_prompts');

    // Check that we have prompts for all major accounting models
    expect($contextPrompts)->toHaveKey('invoice');
    expect($contextPrompts)->toHaveKey('journalentry');
    expect($contextPrompts)->toHaveKey('vendorbill');
    expect($contextPrompts)->toHaveKey('partner');
    expect($contextPrompts)->toHaveKey('account');
    expect($contextPrompts)->toHaveKey('payment');
    expect($contextPrompts)->toHaveKey('bankstatement');
    expect($contextPrompts)->toHaveKey('adjustmentdocument');

    // Check that prompts are meaningful and not empty
    expect($contextPrompts['invoice'])->toContain('profit margin');
    expect($contextPrompts['journalentry'])->toContain('balanced');
    expect($contextPrompts['vendorbill'])->toContain('accuracy');
});

it('has proper eager loading relationships configured', function () {
    $relationships = config('filament-ai-helper.assistant.eager_load_relationships');

    // Check that we have the key relationships for context
    expect($relationships)->toContain('partner');
    expect($relationships)->toContain('company');
    expect($relationships)->toContain('currency');
    expect($relationships)->toContain('invoiceLines.product');
    expect($relationships)->toContain('journalEntryLines.account');
    expect($relationships)->toContain('vendorBillLines.product');
});

it('has iraqi accounting context in system prompt', function () {
    $systemPrompt = config('filament-ai-helper.assistant.system_prompt');

    expect($systemPrompt)->toContain('Iraqi');
    expect($systemPrompt)->toContain('GAAP');
    expect($systemPrompt)->toContain('IFRS');
    expect($systemPrompt)->toContain('immutability');
    expect($systemPrompt)->toContain('multi-currency');
});

it('works correctly with multi-tenancy context', function () {
    // Create test data for the current company
    $partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'customer_id' => $partner->id,
    ]);

    // Test that the AI Helper can access the invoice with correct company context
    $response = $this->get(route('filament.jmeryar.resources.invoices.edit', [
        'tenant' => $this->company,
        'record' => $invoice
    ]));

    $response->assertSuccessful();

    // Verify that the invoice belongs to the correct company
    expect($invoice->company_id)->toBe($this->company->id);
    expect($partner->company_id)->toBe($this->company->id);

    // The AI Helper will automatically work with the correct tenant context
    // since it gets the record through Filament's page context
});

it('can handle form fill requests on create pages', function () {
    // Mock the AI service to return form manipulation response
    $this->mock(\AccounTech\FilamentAiHelper\Services\GeminiService::class, function ($mock) {
        $mock->shouldReceive('generateResponse')
            ->andReturn('{"action": "fill_form", "fields": {"partner_id": "1", "amount": "1000", "date": "2024-01-15"}, "explanation": "Created invoice for customer with amount 1000", "warnings": []}');
    });

    $response = $this->postJson('/api/ai-helper/chat', [
        'message' => 'Create an invoice for customer ABC with amount 1000',
        'model_class' => 'App\\Models\\Invoice',
        'model_id' => 'new',
        'resource_class' => 'App\\Filament\\Resources\\InvoiceResource',
        'form_schema' => [
            'partner_id' => ['type' => 'select', 'required' => true],
            'amount' => ['type' => 'number', 'required' => true],
            'date' => ['type' => 'date', 'required' => true],
        ],
        'form_data' => [],
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'fill_form',
            'fields' => [
                'partner_id' => '1',
                'amount' => '1000',
                'date' => '2024-01-15',
            ],
        ])
        ->assertJsonStructure([
            'success',
            'action',
            'fields',
            'response',
            'warnings',
            'timestamp',
        ]);
});

it('can handle form update requests on edit pages', function () {
    // Mock the AI service to return form manipulation response
    $this->mock(\AccounTech\FilamentAiHelper\Services\GeminiService::class, function ($mock) {
        $mock->shouldReceive('generateResponse')
            ->andReturn('{"action": "update_form", "fields": {"amount": "1500", "due_date": "2024-02-15"}, "explanation": "Updated invoice amount and due date", "warnings": ["This will affect the payment schedule"]}');
    });

    $response = $this->postJson('/api/ai-helper/chat', [
        'message' => 'Change the amount to 1500 and set due date to February 15',
        'model_class' => 'App\\Models\\Invoice',
        'model_id' => '123',
        'resource_class' => 'App\\Filament\\Resources\\InvoiceResource',
        'form_schema' => [
            'amount' => ['type' => 'number', 'required' => true],
            'due_date' => ['type' => 'date', 'required' => false],
        ],
        'form_data' => [
            'amount' => '1000',
            'due_date' => '2024-01-31',
        ],
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'update_form',
            'fields' => [
                'amount' => '1500',
                'due_date' => '2024-02-15',
            ],
            'warnings' => ['This will affect the payment schedule'],
        ])
        ->assertJsonStructure([
            'success',
            'action',
            'fields',
            'response',
            'warnings',
            'timestamp',
        ]);
});

it('can detect form manipulation keywords in messages', function () {
    $controller = new \AccounTech\FilamentAiHelper\Http\Controllers\AiChatController(
        app(\AccounTech\FilamentAiHelper\Actions\GetAIAssistantResponseAction::class),
        app(\AccounTech\FilamentAiHelper\Actions\FillFormAction::class),
        app(\AccounTech\FilamentAiHelper\Actions\UpdateFormAction::class),
        app(\AccounTech\FilamentAiHelper\Services\FormSchemaExtractor::class)
    );

    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('isFormManipulationRequest');
    $method->setAccessible(true);

    // Test form manipulation keywords
    expect($method->invoke($controller, 'fill the form with customer data'))->toBeTrue();
    expect($method->invoke($controller, 'create an invoice for ABC company'))->toBeTrue();
    expect($method->invoke($controller, 'update the amount to 1500'))->toBeTrue();
    expect($method->invoke($controller, 'change the due date'))->toBeTrue();
    expect($method->invoke($controller, 'set the customer field'))->toBeTrue();

    // Test non-form manipulation messages
    expect($method->invoke($controller, 'what is this invoice about?'))->toBeFalse();
    expect($method->invoke($controller, 'analyze the profit margin'))->toBeFalse();
    expect($method->invoke($controller, 'hello how are you?'))->toBeFalse();
});

it('validates form data against schema correctly', function () {
    $extractor = app(\AccounTech\FilamentAiHelper\Services\FormSchemaExtractor::class);

    $schema = [
        'partner_id' => ['type' => 'select', 'required' => true],
        'amount' => ['type' => 'number', 'required' => true],
        'date' => ['type' => 'date', 'required' => false],
        'active' => ['type' => 'boolean', 'required' => false],
    ];

    // Valid data
    $validData = [
        'partner_id' => '1',
        'amount' => '1000',
        'date' => '2024-01-15',
        'active' => true,
    ];
    $errors = $extractor->validateFormData($validData, $schema);
    expect($errors)->toBeEmpty();

    // Missing required field
    $invalidData = [
        'amount' => '1000',
        'date' => '2024-01-15',
    ];
    $errors = $extractor->validateFormData($invalidData, $schema);
    expect($errors)->toHaveKey('partner_id');

    // Invalid date format
    $invalidDateData = [
        'partner_id' => '1',
        'amount' => '1000',
        'date' => 'invalid-date',
    ];
    $errors = $extractor->validateFormData($invalidDateData, $schema);
    expect($errors)->toHaveKey('date');
});
