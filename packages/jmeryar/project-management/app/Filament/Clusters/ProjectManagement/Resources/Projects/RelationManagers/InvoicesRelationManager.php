<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\RelationManagers;

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
                    ->label(__('projectmanagement::project.form.labels.period_start')),
                TextColumn::make('period_end')
                    ->date()
                    ->label(__('projectmanagement::project.form.labels.period_end')),
                TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->project->company->currency->code ?? 'USD')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('invoice.document_number')
                    ->label(__('projectmanagement::project.form.labels.invoice_number')),
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
