<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountService
{
    public function create(array $data): Account
    {
        Validator::make($data, [
            'code' => [
                'required',
                // This rule checks if the code is unique for the given company_id
                Rule::unique('accounts')->where('company_id', $data['company_id'])
            ],
            // ... other rules
        ])->validate();

        return Account::create($data);
    }
}
