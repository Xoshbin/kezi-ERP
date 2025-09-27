<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Accounting\Models\LoanAgreement;

class FeeLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'feeLines';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('currency_id')
                ->default(function () {
                    $owner = $this->getOwnerRecord();

                    return $owner instanceof LoanAgreement ? $owner->currency_id : null;
                }),
            DatePicker::make('date')->required(),
            Select::make('type')
                ->options(collect(FeeType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->name])->toArray())
                ->required(),
            \Modules\Foundation\App\Filament\Forms\Components\MoneyInput::make('amount')->currencyField('currency_id')->required(),
            Toggle::make('capitalize')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('date')->date(),
            TextColumn::make('type')->badge(),
            \Modules\Foundation\App\Filament\Tables\Columns\MoneyColumn::make('amount'),
            IconColumn::make('capitalize')->boolean(),
        ]);
    }
}
