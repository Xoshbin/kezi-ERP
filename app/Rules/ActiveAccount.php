<?php

namespace App\Rules;

use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\Account;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveAccount implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $account = Account::find($value);

        if ($account && $account->is_deprecated) {
            $fail("The account '{$account->name}' is deprecated and cannot be used.");
        }
    }
}
