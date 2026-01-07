<?php

namespace Modules\ProjectManagement\Filament\Resources\Projects\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_date';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('invoice_date')
                    ->required(),
                DatePicker::make('period_start')
                    ->required(),
                DatePicker::make('period_end')
                    ->required(),
                TextInput::make('total_amount')
                    ->numeric()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_start')
                    ->date()
                    ->label('Period Start'),
                TextColumn::make('period_end')
                    ->date()
                    ->label('Period End'),
                TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->project->company->currency->code ?? 'USD')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('invoice.document_number')
                    ->label('Invoice #'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Usually generated from the Project Invoicing tool
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
