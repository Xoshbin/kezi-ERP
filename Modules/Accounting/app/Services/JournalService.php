<?php

namespace App\Services;

use App\Models\Journal;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JournalService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Journal
    {
        Validator::make($data, [
            'short_code' => [
                'required',
                // This rule checks if the short_code is unique for the given company_id.
                Rule::unique('journals')->where('company_id', $data['company_id']),
            ],
            // ... other rules
        ])->validate();

        return Journal::create($data);
    }
}
