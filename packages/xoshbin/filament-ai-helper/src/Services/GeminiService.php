<?php

namespace Xoshbin\FilamentAiHelper\Services;

use Xoshbin\FilamentAiHelper\Exceptions\GeminiApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class GeminiService
{
    protected Client $httpClient;
    protected string $apiKey;
    protected string $apiUrl;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct(
        string $apiKey,
        string $apiUrl,
        int $timeout = 30,
        int $maxRetries = 3
    ) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;

        $this->httpClient = new Client([
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        // For demo purposes, allow empty API key and return mock responses
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured. Using mock responses for demo.');
        }
    }

    /**
     * Generate a response from the Gemini API
     */
    public function generateResponse(string $prompt, array $context = []): string
    {
        // If no API key, return mock response for demo
        if (empty($this->apiKey)) {
            return $this->getMockResponse($prompt, $context);
        }

        $cacheKey = $this->generateCacheKey($prompt, $context);

        if (config('filament-ai-helper.cache.enabled', true)) {
            $cachedResponse = Cache::get($cacheKey);
            if ($cachedResponse) {
                return $cachedResponse;
            }
        }

        $response = $this->makeApiRequest($prompt, $context);
        $generatedText = $this->parseResponse($response);

        if (config('filament-ai-helper.cache.enabled', true)) {
            Cache::put(
                $cacheKey,
                $generatedText,
                config('filament-ai-helper.cache.ttl', 3600)
            );
        }

        return $generatedText;
    }

    /**
     * Make the actual API request to Gemini
     */
    protected function makeApiRequest(string $prompt, array $context = []): ResponseInterface
    {
        $requestData = $this->buildRequestData($prompt, $context);
        $url = $this->buildApiUrl();

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->httpClient->post($url, [
                    'json' => $requestData,
                ]);

                if (config('filament-ai-helper.security.log_requests', false)) {
                    Log::info('Gemini API request successful', [
                        'attempt' => $attempt + 1,
                        'status_code' => $response->getStatusCode(),
                    ]);
                }

                return $response;

            } catch (RequestException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $delay = pow(2, $attempt); // Exponential backoff
                    sleep($delay);
                }

                Log::warning('Gemini API request failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'status_code' => $e->getResponse()?->getStatusCode(),
                ]);
            }
        }

        throw new GeminiApiException(
            'Failed to get response from Gemini API after ' . $this->maxRetries . ' attempts: ' .
            ($lastException ? $lastException->getMessage() : 'Unknown error'),
            0,
            $lastException
        );
    }

    /**
     * Build the request data for the Gemini API
     */
    protected function buildRequestData(string $prompt, array $context = []): array
    {
        return [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
    }

    /**
     * Build the complete API URL with the API key
     */
    protected function buildApiUrl(): string
    {
        return $this->apiUrl . '?key=' . $this->apiKey;
    }

    /**
     * Parse the response from the Gemini API
     */
    protected function parseResponse(ResponseInterface $response): string
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GeminiApiException('Invalid JSON response from Gemini API: ' . json_last_error_msg());
        }

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new GeminiApiException('Unexpected response format from Gemini API');
        }

        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    /**
     * Generate a cache key for the request
     */
    protected function generateCacheKey(string $prompt, array $context = []): string
    {
        $keyData = [
            'prompt' => $prompt,
            'context' => $context,
        ];

        return config('filament-ai-helper.cache.key_prefix', 'filament_ai_helper') . ':' .
               md5(serialize($keyData));
    }

    /**
     * Get a mock response for demo purposes when API key is not configured
     */
    protected function getMockResponse(string $prompt, array $context = []): string
    {
        // Check if this is a form manipulation request
        if (str_contains(strtolower($prompt), 'fill') || str_contains(strtolower($prompt), 'create') || str_contains(strtolower($prompt), 'invoice')) {
            return json_encode([
                'action' => 'fill_form',
                'fields' => [
                    'partner_id' => '1',
                    'due_date' => '2025-08-23',
                    'description' => 'Service provided by Hawre Trading',
                    'unit_price' => '5000000',
                    'quantity' => '1'
                ],
                'explanation' => 'I have filled the form with the invoice details for Hawre Trading. The service amount is set to 5,000,000 IQD as requested.',
                'warnings' => ['Please verify the customer details and due date before saving.']
            ]);
        }

        // Default response for other requests
        return 'This is a demo response from the AI Helper. To enable full functionality, please configure your Gemini API key in the .env file.';
    }

    /**
     * Test the API connection
     */
    public function testConnection(): bool
    {
        try {
            $this->generateResponse('Hello, this is a test message.');
            return true;
        } catch (GeminiApiException $e) {
            Log::error('Gemini API connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
