<?php

namespace Modules\Accounting\Enums\Budgets;

enum BudgetStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';

    /**
     * Get the translated label for the budget status.
     */
    public function label(): string
    {
        return __('enums.budget_status.'.$this->value);
    }
}
