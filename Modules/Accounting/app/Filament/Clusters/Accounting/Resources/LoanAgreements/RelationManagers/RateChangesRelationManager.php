<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RateChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'rateChanges';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('effective_date')->required(),
            TextInput::make('annual_rate')->numeric()->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('effective_date')->date(),
            TextColumn::make('annual_rate')->suffix('%'),
        ]);
    }
}
