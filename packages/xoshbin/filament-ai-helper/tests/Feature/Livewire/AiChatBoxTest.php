<?php

use Xoshbin\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use Xoshbin\FilamentAiHelper\Livewire\AiChatBox;
use Xoshbin\FilamentAiHelper\Services\GeminiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// Mock model for testing
class TestInvoice extends Model
{
    protected $table = 'test_invoices';
    protected $fillable = ['number', 'total'];

    public static function find($id)
    {
        if ($id === 1) {
            $model = new static();
            $model->id = 1;
            $model->number = 'INV-001';
            $model->total = 1000;
            return $model;
        }
        return null;
    }
}

beforeEach(function () {
    // Mock the GeminiService
    $this->mock(GeminiService::class, function ($mock) {
        $mock->shouldReceive('generateResponse')
            ->andReturn('This is a test AI response');
    });

    // Mock the action
    $this->mock(GetAIAssistantResponseAction::class, function ($mock) {
        $mock->shouldReceive('execute')
            ->andReturn('This is a test AI response');
        $mock->shouldReceive('generateWelcomeMessage')
            ->andReturn('Welcome! I can help you with this record.');
    });
});

it('can render the component', function () {
    Livewire::test(AiChatBox::class)
        ->assertStatus(200);
});

it('initializes with welcome message when model exists', function () {
    Livewire::test(AiChatBox::class, [
        'modelClass' => TestInvoice::class,
        'modelId' => '1',
        'resourceClass' => 'TestResource'
    ])
        ->assertSet('modelClass', TestInvoice::class)
        ->assertSet('modelId', '1')
        ->assertSet('resourceClass', 'TestResource')
        ->assertCount('messages', 1)
        ->assertSee('Welcome! I can help you with this record.');
});

it('initializes with default message when no model', function () {
    Livewire::test(AiChatBox::class)
        ->assertCount('messages', 1)
        ->assertSee('AccounTech Pro');
});

it('can send a message successfully', function () {
    Livewire::test(AiChatBox::class, [
        'modelClass' => TestInvoice::class,
        'modelId' => '1',
        'resourceClass' => 'TestResource'
    ])
        ->set('currentQuestion', 'What is the total amount?')
        ->call('sendMessage')
        ->assertSet('currentQuestion', '')
        ->assertSet('isLoading', false)
        ->assertCount('messages', 3) // Welcome + user + assistant
        ->assertSee('What is the total amount?')
        ->assertSee('This is a test AI response');
});

it('validates required question', function () {
    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', '')
        ->call('sendMessage')
        ->assertHasErrors(['currentQuestion' => 'required']);
});

it('validates minimum question length', function () {
    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Hi')
        ->call('sendMessage')
        ->assertHasErrors(['currentQuestion' => 'min']);
});

it('validates maximum question length', function () {
    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', str_repeat('a', 1001))
        ->call('sendMessage')
        ->assertHasErrors(['currentQuestion' => 'max']);
});

it('handles rate limiting', function () {
    config(['filament-ai-helper.security.rate_limit.enabled' => true]);

    // Simulate rate limit exceeded
    RateLimiter::hit('ai-helper:127.0.0.1', 60);

    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question')
        ->call('sendMessage')
        ->assertHasErrors(['currentQuestion']);
});

it('can clear chat history', function () {
    Livewire::test(AiChatBox::class, [
        'modelClass' => TestInvoice::class,
        'modelId' => '1',
        'resourceClass' => 'TestResource'
    ])
        ->set('currentQuestion', 'Test question')
        ->call('sendMessage')
        ->assertCount('messages', 3)
        ->call('clearChat')
        ->assertCount('messages', 1) // Only welcome message remains
        ->assertSet('hasError', false)
        ->assertSet('errorMessage', '');
});

it('shows loading state during message processing', function () {
    // Mock a slow response
    $this->mock(GetAIAssistantResponseAction::class, function ($mock) {
        $mock->shouldReceive('execute')
            ->andReturnUsing(function () {
                // Simulate processing time
                return 'Delayed response';
            });
        $mock->shouldReceive('generateWelcomeMessage')
            ->andReturn('Welcome message');
    });

    $component = Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question');

    // Check that loading state is set during processing
    $component->call('sendMessage');

    // After processing, loading should be false
    $component->assertSet('isLoading', false);
});

it('handles api errors gracefully', function () {
    // Mock an error response
    $this->mock(GetAIAssistantResponseAction::class, function ($mock) {
        $mock->shouldReceive('execute')
            ->andThrow(new \Exception('API Error'));
        $mock->shouldReceive('generateWelcomeMessage')
            ->andReturn('Welcome message');
    });

    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question')
        ->call('sendMessage')
        ->assertSet('hasError', true)
        ->assertSet('errorMessage', 'Sorry, I encountered an error while processing your request. Please try again.')
        ->assertSee('Sorry, I encountered an error');
});

it('gets record info correctly', function () {
    $component = Livewire::test(AiChatBox::class, [
        'modelClass' => TestInvoice::class,
        'modelId' => '1',
        'resourceClass' => 'TestResource'
    ]);

    $recordInfo = $component->get('recordInfo');

    expect($recordInfo['type'])->toBe('TestInvoice');
    expect($recordInfo['identifier'])->toBe('INV-001');
    expect($recordInfo['exists'])->toBeTrue();
});

it('handles non-existent record', function () {
    $component = Livewire::test(AiChatBox::class, [
        'modelClass' => TestInvoice::class,
        'modelId' => '999',
        'resourceClass' => 'TestResource'
    ]);

    $recordInfo = $component->get('recordInfo');

    expect($recordInfo['type'])->toBe('TestInvoice');
    expect($recordInfo['identifier'])->toBe('N/A');
    expect($recordInfo['exists'])->toBeFalse();
});

it('handles empty model class', function () {
    $component = Livewire::test(AiChatBox::class, [
        'modelClass' => '',
        'modelId' => '',
        'resourceClass' => ''
    ]);

    $recordInfo = $component->get('recordInfo');

    expect($recordInfo['type'])->toBe('Unknown');
    expect($recordInfo['identifier'])->toBe('N/A');
    expect($recordInfo['exists'])->toBeFalse();
});

it('respects rate limiting configuration', function () {
    config(['filament-ai-helper.security.rate_limit.enabled' => false]);

    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question')
        ->call('sendMessage')
        ->assertSet('isLoading', false)
        ->assertHasNoErrors();
});

it('adds messages with correct structure', function () {
    $component = Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question')
        ->call('sendMessage');

    $messages = $component->get('messages');

    // Check that messages have the correct structure
    foreach ($messages as $message) {
        expect($message)->toHaveKeys(['type', 'content', 'timestamp']);
        expect($message['type'])->toBeIn(['user', 'assistant']);
        expect($message['content'])->toBeString();
        expect($message['timestamp'])->toBeString();
    }
});

it('handles ctrl+enter keyboard shortcut', function () {
    Livewire::test(AiChatBox::class)
        ->set('currentQuestion', 'Test question')
        ->dispatch('keydown.ctrl.enter')
        ->assertCount('messages', 3); // Default + user + assistant
});
