<?php

namespace Kezi\Foundation\Filament\Forms\Components;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Kezi\Foundation\Models\PaymentTerm;
use Xoshbin\TranslatableSelect\Components\TranslatableSelect;

class PaymentTermSelectField extends TranslatableSelect
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->modelClass = PaymentTerm::class;
        $this->relationshipTitleAttribute = 'name';
        $this->configureForModel();

        $this->label(__('foundation::payment_term.label'));
        $this->searchable();
        $this->preload();

        $this->modifyQueryUsing(function ($query) {
            return $query->where('company_id', Filament::getTenant()?->getKey())
                ->active();
        });

        $this->createOptionForm([
            TextInput::make('name')
                ->label(__('foundation::payment_term.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('description')
                ->label(__('foundation::payment_term.description'))
                ->maxLength(255),
            Toggle::make('is_active')
                ->label(__('foundation::payment_term.is_active'))
                ->default(true),
        ]);

        $this->createOptionModalHeading(__('foundation::payment_term.create_payment_term'));

        $this->createOptionAction(function (Action $action) {
            return $action->modalWidth('md');
        });

        $this->createOptionUsing(function (array $data): int {
            $term = PaymentTerm::create([
                'company_id' => Filament::getTenant()?->getKey(),
                'name' => ['en' => $data['name']],
                'description' => ['en' => $data['description'] ?? null],
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $term->getKey();
        });
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }
}
