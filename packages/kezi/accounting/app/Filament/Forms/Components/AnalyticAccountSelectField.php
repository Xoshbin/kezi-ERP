<?php

namespace Kezi\Accounting\Filament\Forms\Components;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Kezi\Accounting\Models\AnalyticAccount;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class AnalyticAccountSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = AnalyticAccount::class;
        $this->relationshipTitleAttribute = 'name';
        $this->configureForModel();

        $this->label(__('accounting::analytic_account.analytic_account'));
        $this->searchable();
        $this->preload();

        $this->modifyQueryUsing(function ($query) {
            return $query->where('company_id', Filament::getTenant()?->getKey())
                ->where('is_active', true);
        });

        $this->createOptionForm([
            TextInput::make('name')
                ->label(__('accounting::analytic_account.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('reference')
                ->label(__('accounting::analytic_account.reference'))
                ->maxLength(255),
            Toggle::make('is_active')
                ->label(__('accounting::analytic_account.is_active'))
                ->default(true)
                ->required(),
        ]);

        $this->createOptionModalHeading(__('accounting::analytic_account.create_analytic_account'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('md');
        });

        $this->createOptionUsing(function (array $data): int {
            $account = AnalyticAccount::create([
                'company_id' => Filament::getTenant()?->getKey(),
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $account->getKey();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
