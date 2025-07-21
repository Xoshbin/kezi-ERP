<?php

namespace App\Services;

use App\Rules\ActiveAccount;
use Illuminate\Support\Facades\Validator;

class JournalEntryService
{
    public function create(array $data)
    {
        Validator::make($data, [
            // Apply the rule to each account_id in the lines array
            'lines.*.account_id' => ['required', 'exists:accounts,id', new ActiveAccount],
            // ... other rules
        ])->validate();

        // ... logic to create the journal entry
    }
}
