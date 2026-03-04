<?php

namespace Kezi\Accounting\Filament\Forms\Components;

use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\RootAccountType;
use Kezi\Accounting\Models\Account;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class AccountSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = Account::class;
        $this->relationshipTitleAttribute = 'name';

        $this->label(__('accounting::account.label'))
            ->searchable()
            ->preload();

        $this->configureForModel();

        $this->searchableFields(['name', 'code'])
            ->modifyQueryUsing(function ($query) {
                $query->where('company_id', Filament::getTenant()?->getKey())
                    ->where('is_deprecated', false);

                if ($filter = $this->getAccountFilter()) {
                    $filter($query);
                }

                return $query;
            })
            ->createOptionForm([
                TextInput::make('code')
                    ->label(__('accounting::account.code'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label(__('accounting::account.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label(__('accounting::account.type'))
                    ->required()
                    ->options(
                        collect(AccountType::cases())
                            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
                    )
                    ->searchable(),
                Toggle::make('is_deprecated')
                    ->label(__('accounting::account.is_deprecated'))
                    ->default(false),
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => Filament::getTenant()?->id),
            ])
            ->createOptionUsing(function (array $data): int {
                return Account::create($data)->id;
            });
    }

    protected ?Closure $accountFilter = null;

    /**
     * @param  Closure|string|array<string>|null  $callback
     */
    public function accountFilter(Closure|string|array|null $callback): static
    {
        if (is_string($callback) || is_array($callback)) {
            $types = (array) $callback;
            $callback = function ($query) use ($types) {
                $query->where(function ($query) use ($types) {
                    foreach ($types as $type) {
                        // Check if it's a RootAccountType enum value string
                        $rootType = RootAccountType::tryFrom($type);
                        if ($rootType) {
                            $accountTypes = collect(AccountType::cases())
                                ->filter(fn (AccountType $at) => $at->rootType() === $rootType)
                                ->map(fn (AccountType $at) => $at->value)
                                ->toArray();
                            $query->orWhereIn('type', $accountTypes);
                        } else {
                            // Assume it's a specific AccountType value
                            $query->orWhere('type', $type);
                        }
                    }
                });
            };
        }

        $this->accountFilter = $callback;

        return $this;
    }

    public function getAccountFilter(): ?Closure
    {
        return $this->accountFilter;
    }
}
