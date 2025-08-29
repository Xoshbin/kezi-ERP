<?php

use Xoshbin\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Xoshbin\FilamentAiHelper\Exceptions\GeminiApiException;
use Xoshbin\FilamentAiHelper\Services\GeminiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

// Mock model for testing
class InvoiceModel extends Model
{
    protected $table = 'invoices';
    protected $fillable = ['number', 'total', 'customer_id'];

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class);
    }

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLineModel::class);
    }
}

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $fillable = ['name', 'email'];
}

class InvoiceLineModel extends Model
{
    protected $table = 'invoice_lines';
    protected $fillable = ['product_id', 'quantity', 'price'];

    public function product()
    {
        return $this->belongsTo(ProductModel::class);
    }
}

class ProductModel extends Model
{
    protected $table = 'products';
    protected $fillable = ['name', 'price'];
}

beforeEach(function () {
    Log::spy();
});

it('can execute successfully with valid context', function () {
    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->andReturn('This is a test AI response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'What is the total amount?',
        locale: 'en'
    );

    $response = $action->execute($context);

    expect($response)->toBe('This is a test AI response');
});

it('handles gemini api exceptions gracefully', function () {
    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->andThrow(new GeminiApiException('API Error'));

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'What is the total amount?',
        locale: 'en'
    );

    $response = $action->execute($context);

    expect($response)->toContain('unable to analyze this Invoice record');
    Log::shouldHaveReceived('error')->once();
});

it('handles unexpected exceptions gracefully', function () {
    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->andThrow(new \Exception('Unexpected error'));

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'What is the total amount?',
        locale: 'en'
    );

    $response = $action->execute($context);

    expect($response)->toContain('unable to analyze this Invoice record');
    Log::shouldHaveReceived('error')->once();
});

it('builds correct system prompt with locale', function () {
    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, 'All responses must be in the en language');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $action->execute($context);
});

it('includes context data in prompt when model exists', function () {
    $model = new InvoiceModel();
    $model->id = 1;
    $model->number = 'INV-001';
    $model->total = 1000;

    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, 'Record Type: InvoiceModel') &&
                   str_contains($prompt, 'Record ID: 1') &&
                   str_contains($prompt, 'INV-001');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en',
        record: $model
    );

    $action->execute($context);
});

it('handles missing model gracefully', function () {
    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, 'No record data available');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: 'NonExistentModel',
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $action->execute($context);
});

it('uses specific task instructions for known model types', function () {
    config([
        'filament-ai-helper.assistant.context_prompts' => [
            'invoice' => 'Analyze this invoice for profit margins',
            'default' => 'General analysis'
        ]
    ]);

    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, 'Analyze this invoice for profit margins');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $action->execute($context);
});

it('falls back to default task instructions for unknown model types', function () {
    config([
        'filament-ai-helper.assistant.context_prompts' => [
            'invoice' => 'Analyze this invoice for profit margins',
            'default' => 'General analysis'
        ]
    ]);

    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, 'General analysis');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: 'UnknownModel',
        modelId: 1,
        resourceClass: 'UnknownResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $action->execute($context);
});

it('generates welcome message with record context', function () {
    $model = new InvoiceModel();
    $model->id = 1;
    $model->number = 'INV-001';

    $action = new GetAIAssistantResponseAction(Mockery::mock(GeminiService::class));

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: '',
        locale: 'en',
        record: $model
    );

    $welcomeMessage = $action->generateWelcomeMessage($context);

    expect($welcomeMessage)->toContain('InvoiceModel INV-001');
    expect($welcomeMessage)->toContain('AccounTech Pro');
});

it('generates default welcome message when no record', function () {
    $action = new GetAIAssistantResponseAction(Mockery::mock(GeminiService::class));

    $context = new AIHelperContextDTO(
        modelClass: 'NonExistentModel',
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: '',
        locale: 'en'
    );

    $welcomeMessage = $action->generateWelcomeMessage($context);

    expect($welcomeMessage)->toContain('Hello! I\'m AccounTech Pro');
});

it('returns empty welcome message when disabled', function () {
    config(['filament-ai-helper.ui.enable_welcome_message' => false]);

    $action = new GetAIAssistantResponseAction(Mockery::mock(GeminiService::class));

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: '',
        locale: 'en'
    );

    $welcomeMessage = $action->generateWelcomeMessage($context);

    expect($welcomeMessage)->toBe('');
});

it('truncates context data when too long', function () {
    config(['filament-ai-helper.assistant.max_context_length' => 50]);

    $model = new InvoiceModel();
    $model->id = 1;
    $model->number = 'INV-001';
    $model->description = str_repeat('Very long description ', 100); // Make it very long

    $geminiService = Mockery::mock(GeminiService::class);
    $geminiService->shouldReceive('generateResponse')
        ->once()
        ->with(Mockery::on(function ($prompt) {
            return str_contains($prompt, '... (truncated)');
        }), Mockery::any())
        ->andReturn('Test response');

    $action = new GetAIAssistantResponseAction($geminiService);

    $context = new AIHelperContextDTO(
        modelClass: InvoiceModel::class,
        modelId: 1,
        resourceClass: 'InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en',
        record: $model
    );

    $action->execute($context);
});
