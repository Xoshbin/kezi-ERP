<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;

class ScheduleEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'scheduleEntries';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->paginated(false)->columns([
            TextColumn::make('sequence')->sortable(),
            TextColumn::make('due_date')->date(),
            MoneyColumn::make('payment_amount'),
            MoneyColumn::make('principal_component'),
            MoneyColumn::make('interest_component'),
            MoneyColumn::make('outstanding_balance_after')->label(__('accounting::loan.balance_after')),
        ]);
    }
}
