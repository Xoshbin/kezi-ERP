<?php

namespace App\Rules;

use Closure;
use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;

class NumberingSettingsChangeRule implements ValidationRule
{
    public function __construct(
        protected Company $company
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->company->canChangeNumberingSettings()) {
            $errors = $this->company->getNumberingChangeValidationErrors();
            $fail(__('numbering.validation.cannot_change_posted_exist') . ' (' . implode(', ', $errors) . ')');
        }
    }
}
