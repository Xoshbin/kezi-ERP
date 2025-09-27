<?php

namespace Modules\Foundation\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Validator;

class CurrencyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Currency
    {
        Validator::make($data, [
            'code' => ['required', 'string', 'unique:currencies,code'],
            'name' => ['required', 'string'],
            // ... other rules
        ])->validate(); // Throws ValidationException on failure

        return Currency::create($data);
    }
}
