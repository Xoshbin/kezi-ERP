<?php

namespace Modules\Foundation\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Company
    {
        // Use Laravel's built-in Validator to check the data first.
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'fiscal_country' => ['required', 'string'],
            'tax_id' => [
                'nullable',
                'string',
                // This is the key rule: tax_id must be unique for the given fiscal_country.
                Rule::unique('companies')->where(function ($query) use ($data) {
                    return $query->where('fiscal_country', $data['fiscal_country']);
                }),
            ],
            // ... other validation rules
        ])->validate(); // This will automatically throw the ValidationException if it fails.

        return Company::create($data);
    }
}
