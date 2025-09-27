<?php

namespace Modules\Accounting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\Accounting\Models\Account;

class ActiveAccount implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $account = Account::find($value);
        // Ensure we have a single Account model, not a collection
        if ($account instanceof Collection) {
            $account = $account->first();
        }

        if ($account && $account->is_deprecated) {
            $accountName = is_array($account->name) ? ($account->name['en'] ?? (empty($account->name) ? '' : (string) array_values($account->name)[0])) : (string) $account->name;
            $fail("The account '{$accountName}' is deprecated and cannot be used.");
        }
    }
}
