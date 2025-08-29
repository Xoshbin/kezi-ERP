<?php

use AccounTech\FilamentAiHelper\Exceptions\GeminiApiException;
use AccounTech\FilamentAiHelper\Services\GeminiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('can be instantiated with required parameters', function () {
    $service = new GeminiService(
        apiKey: 'test-key',
        apiUrl: 'https://api.test.com',
        timeout: 30,
        maxRetries: 3
    );

    expect($service)->toBeInstanceOf(GeminiService::class);
});

it('allows empty api key for demo mode', function () {
    $service = new GeminiService('', 'https://api.test.com');
    expect($service)->toBeInstanceOf(GeminiService::class);
});

it('can generate response successfully', function () {
    $mockResponse = [
        'candidates' => [
            [
                'content' => [
                    'parts' => [
                        ['text' => 'This is a test response from Gemini API']
                    ]
                ]
            ]
        ]
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com');

    // Use reflection to replace the HTTP client
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    $response = $service->generateResponse('Test prompt');

    expect($response)->toBe('This is a test response from Gemini API');
});

it('handles api errors gracefully', function () {
    $mock = new MockHandler([
        new RequestException('API Error', new Request('POST', 'test'))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com', maxRetries: 1);

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    expect(fn () => $service->generateResponse('Test prompt'))
        ->toThrow(GeminiApiException::class);
});

it('retries failed requests', function () {
    $mock = new MockHandler([
        new RequestException('First failure', new Request('POST', 'test')),
        new RequestException('Second failure', new Request('POST', 'test')),
        new Response(200, [], json_encode([
            'candidates' => [
                ['content' => ['parts' => [['text' => 'Success after retries']]]]
            ]
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com', maxRetries: 3);

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    $response = $service->generateResponse('Test prompt');

    expect($response)->toBe('Success after retries');
});

it('handles invalid json response', function () {
    $mock = new MockHandler([
        new Response(200, [], 'invalid json')
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com');

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    expect(fn () => $service->generateResponse('Test prompt'))
        ->toThrow(GeminiApiException::class, 'Invalid JSON response');
});

it('handles unexpected response format', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode(['unexpected' => 'format']))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com');

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    expect(fn () => $service->generateResponse('Test prompt'))
        ->toThrow(GeminiApiException::class, 'Unexpected response format');
});

it('caches responses when enabled', function () {
    config(['filament-ai-helper.cache.enabled' => true]);

    $mockResponse = [
        'candidates' => [
            ['content' => ['parts' => [['text' => 'Cached response']]]]
        ]
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com');

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    // First call should hit the API
    $response1 = $service->generateResponse('Test prompt');

    // Second call should use cache (no additional HTTP request)
    $response2 = $service->generateResponse('Test prompt');

    expect($response1)->toBe('Cached response');
    expect($response2)->toBe('Cached response');

    // Verify cache was used
    expect(Cache::has('filament_ai_helper:' . md5(serialize(['prompt' => 'Test prompt', 'context' => []]))))->toBeTrue();
});

it('can test connection', function () {
    $mockResponse = [
        'candidates' => [
            ['content' => ['parts' => [['text' => 'Connection test successful']]]]
        ]
    ];

    $mock = new MockHandler([
        new Response(200, [], json_encode($mockResponse))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com');

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    expect($service->testConnection())->toBeTrue();
});

it('returns false for failed connection test', function () {
    $mock = new MockHandler([
        new RequestException('Connection failed', new Request('POST', 'test'))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $service = new GeminiService('test-key', 'https://api.test.com', maxRetries: 1);

    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('httpClient');
    $property->setAccessible(true);
    $property->setValue($service, $client);

    expect($service->testConnection())->toBeFalse();
});
