<?php

namespace Modules\Accounting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Support\TranslatableHelper;

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
            $accountName = TranslatableHelper::getLocalizedValue($account->name);
            $fail("The account '{$accountName}' is deprecated and cannot be used.");
        }
    }
}
