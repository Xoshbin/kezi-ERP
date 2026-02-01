<?php

namespace Jmeryar\Accounting\Enums\Budgets;

enum BudgetType: string
{
    case Analytic = 'analytic';
    case Financial = 'financial';

    /**
     * Get the translated label for the budget type.
     */
    public function label(): string
    {
        return __('enums.budget_type.'.$this->value);
    }
}
