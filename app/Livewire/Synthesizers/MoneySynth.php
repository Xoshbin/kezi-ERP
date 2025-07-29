<?php

namespace App\Livewire\Synthesizers;

use Brick\Money\Money;
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synth;

class MoneySynth extends Synth
{
    public static string $key = 'money';

    public static function match(mixed $target): bool
    {
        return $target instanceof Money;
    }

    /**
     * Dehydrates the Money object into a payload and metadata.
     * MUST return a tuple array: [$payload, $meta].
     */
    public function dehydrate($value)
    {
        return [
            // The payload: a simple array representing the Money object.
            [
                'amount' => (string) $value->getAmount(),
                'currency' => $value->getCurrency()->getCurrencyCode(),
            ],
            // The metadata: an empty array as we don't need it.
            []
        ];
    }

    /**
     * Hydrates the payload back into a Money object.
     */
    public function hydrate($payload)
    {
        if (!isset($payload['amount'], $payload['currency'])) {
            return null;
        }

        return Money::of($payload['amount'], $payload['currency']);
    }
}