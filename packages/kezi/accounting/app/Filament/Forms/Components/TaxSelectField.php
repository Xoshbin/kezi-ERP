<?php

namespace Kezi\Accounting\Filament\Forms\Components;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Models\Tax;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class TaxSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = Tax::class;
        $this->relationshipTitleAttribute = 'name';

        $this->label(__('accounting::tax.label'))
            ->searchable()
            ->preload();

        $this->searchableFields(['name']);

        $this->configureForModel();

        $this->modifyQueryUsing(function ($query) {
            $query->where('company_id', Filament::getTenant()?->getKey())
                ->where('is_active', true);

            if ($filter = $this->getTaxFilter()) {
                $filter($query);
            }

            return $query;
        })
            ->createOptionForm(function () {
                $defaultType = $this->defaultTaxType instanceof TaxType
                    ? $this->defaultTaxType->value
                    : $this->defaultTaxType;

                return [
                    Hidden::make('company_id')
                        ->default(fn () => Filament::getTenant()?->getKey()),
                    AccountSelectField::make('tax_account_id')
                        ->label(__('accounting::tax.tax_account'))
                        ->required(),
                    TextInput::make('name')
                        ->label(__('accounting::tax.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('rate')
                        ->label(__('accounting::tax.rate'))
                        ->required()
                        ->numeric(),
                    Select::make('type')
                        ->label(__('accounting::tax.type'))
                        ->options(collect(TaxType::cases())->mapWithKeys(fn (TaxType $case) => [$case->value => $case->label()]))
                        ->default($defaultType)
                        ->required(),
                    Toggle::make('is_active')
                        ->label(__('accounting::tax.is_active'))
                        ->default(true),
                ];
            })
            ->createOptionUsing(function (array $data): int {
                $data['company_id'] = Filament::getTenant()?->getKey();

                return Tax::create($data)->id;
            })
            ->createOptionModalHeading(__('foundation::common.modal_title_create_tax'))
            ->createOptionAction(function (Action $action) {
                return $action->modalWidth('lg');
            });
    }

    protected TaxType|string|null $defaultTaxType = null;

    public function createOptionDefaultType(TaxType|string|null $type): static
    {
        $this->defaultTaxType = $type;

        return $this;
    }

    public function getDefaultTaxType(): TaxType|string|null
    {
        return $this->defaultTaxType;
    }

    protected ?\Closure $taxFilter = null;

    /**
     * @param  \Closure|TaxType|string|array<int, TaxType|string>|null  $callback
     */
    public function taxFilter(\Closure|TaxType|string|array|null $callback): static
    {
        if ($callback instanceof TaxType || is_string($callback) || is_array($callback)) {
            $types = collect((array) $callback)->map(fn ($t) => $t instanceof TaxType ? $t->value : $t)->toArray();
            $callback = function ($query) use ($types) {
                $query->whereIn('type', $types);
            };
        }

        $this->taxFilter = $callback;

        return $this;
    }

    public function getTaxFilter(): ?\Closure
    {
        return $this->taxFilter;
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
