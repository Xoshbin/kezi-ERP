<?php

namespace Kezi\Foundation\Livewire\Synthesizers;

use Brick\Money\Money;
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;

/**
 * A Livewire Synthesizer for the Brick\Money\Money class.
 *
 * This class acts as a "translator" 🗣️ between the backend (PHP) and the frontend (JavaScript).
 * Livewire doesn't know how to handle complex PHP objects like `Money` by default. This synthesizer
 * teaches Livewire how to convert `Money` objects into a simple array that can be sent to the
 * browser (a process called "dehydration"), and how to convert that simple array back into a
 * `Money` object on the server (a process called "hydration").
 *
 * --- REGISTRATION ---
 * This synthesizer is registered in the `boot()` method of a service provider (e.g., AppServiceProvider):
 * `Livewire::propertySynthesizer(MoneySynth::class);`
 *
 * Because it's registered globally, Livewire automatically uses it for any public property in a
 * component that is a `Money` object. You do not need to call this class directly.
 */
class MoneySynth extends Synth
{
    public static string $key = 'money';

    /**
     * This method is called by Livewire to check if this synthesizer should handle a given property.
     * It returns `true` if the property is an instance of the `Money` class.
     */
    public static function match(mixed $target): bool
    {
        return $target instanceof Money;
    }

    /**
     * "Dehydrates" the Money object into a simple payload and metadata.
     * This is called when PHP data is being sent TO the browser.
     *
     * @param  Money  $value  The Money object instance.
     * @return array{0: array{amount: string, currency: string}, 1: array<string, mixed>} A tuple array: [$payload, $meta] that the browser can understand.
     */
    public function dehydrate($value): array
    {
        return [
            // The payload: a simple array representing the Money object.
            [
                'amount' => (string) $value->getAmount(),
                'currency' => $value->getCurrency()->getCurrencyCode(),
            ],
            // The metadata: an empty array as we don't need to send extra info.
            [],
        ];
    }

    /**
     * "Hydrates" the simple payload from the frontend back into a Money object.
     * This is called when data is coming FROM the browser back to the server.
     *
     * @param  mixed  $payload  The payload from the frontend - can be an array (from dehydrated Money) or string (from form input).
     */
    public function hydrate(mixed $payload): ?Money
    {
        // Handle array payloads (from dehydrated Money objects)
        if (is_array($payload)) {
            if (empty($payload['amount']) || empty($payload['currency'])) {
                return null;
            }

            return Money::of($payload['amount'], $payload['currency']);
        }

        // For non-array payloads (like strings from form inputs), return null
        // and let the MoneyInput component handle the conversion using its getMoneyObject() method
        return null;
    }
}
