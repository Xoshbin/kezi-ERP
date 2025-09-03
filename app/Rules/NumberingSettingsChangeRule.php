<?php

namespace App\Rules;

use App\Models\Company;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NumberingSettingsChangeRule implements ValidationRule
{
    public function __construct(
        protected Company $company
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->company->canChangeNumberingSettings()) {
            $errors = $this->company->getNumberingChangeValidationErrors();
            $fail(__('numbering.validation.cannot_change_posted_exist').' ('.implode(', ', $errors).')');
        }
    }
}
