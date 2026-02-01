<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Jmeryar\Foundation\Filament\Tables\Columns\MoneyColumn;

class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    protected static ?string $recordTitleAttribute = 'description';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('payment.bank_statement_lines_relation_manager.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payment.bank_statement_lines_relation_manager.statement_line_details'))
                    ->schema([
                        DatePicker::make('date')
                            ->label(__('payment.bank_statement_lines_relation_manager.date'))
                            ->required()
                            ->disabled(),

                        Textarea::make('description')
                            ->label(__('payment.bank_statement_lines_relation_manager.description'))
                            ->disabled()
                            ->columnSpanFull(),

                        TextInput::make('partner_name')
                            ->label(__('payment.bank_statement_lines_relation_manager.partner_name'))
                            ->disabled(),

                        TextInput::make('amount')
                            ->label(__('payment.bank_statement_lines_relation_manager.amount'))
                            ->disabled()
                            ->numeric(),

                        Toggle::make('is_reconciled')
                            ->label(__('payment.bank_statement_lines_relation_manager.is_reconciled'))
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make(__('payment.bank_statement_lines_relation_manager.bank_statement_details'))
                    ->schema([
                        Select::make('bankStatement.id')
                            ->label(__('payment.bank_statement_lines_relation_manager.bank_statement'))
                            ->relationship('bankStatement', 'name')
                            ->disabled(),

                        DatePicker::make('bankStatement.statement_date')
                            ->label(__('payment.bank_statement_lines_relation_manager.statement_date'))
                            ->disabled(),

                        TextInput::make('bankStatement.reference')
                            ->label(__('payment.bank_statement_lines_relation_manager.statement_reference'))
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('date')
                    ->label(__('payment.bank_statement_lines_relation_manager.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('payment.bank_statement_lines_relation_manager.description'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('partner_name')
                    ->label(__('payment.bank_statement_lines_relation_manager.partner_name'))
                    ->searchable()
                    ->toggleable(),

                MoneyColumn::make('amount')
                    ->label(__('payment.bank_statement_lines_relation_manager.amount'))
                    ->sortable(),

                IconColumn::make('is_reconciled')
                    ->label(__('payment.bank_statement_lines_relation_manager.reconciliation_status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('bankStatement.name')
                    ->label(__('payment.bank_statement_lines_relation_manager.bank_statement'))
                    ->toggleable(),

                TextColumn::make('bankStatement.statement_date')
                    ->label(__('payment.bank_statement_lines_relation_manager.statement_date'))
                    ->date()
                    ->toggleable(),

                TextColumn::make('bankStatement.reference')
                    ->label(__('payment.bank_statement_lines_relation_manager.statement_reference'))
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('payment.bank_statement_lines_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_reconciled')
                    ->label(__('payment.bank_statement_lines_relation_manager.filter_reconciliation_status'))
                    ->placeholder(__('payment.bank_statement_lines_relation_manager.filter_all'))
                    ->trueLabel(__('payment.bank_statement_lines_relation_manager.filter_reconciled'))
                    ->falseLabel(__('payment.bank_statement_lines_relation_manager.filter_not_reconciled')),

                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label(__('payment.bank_statement_lines_relation_manager.date_from')),
                        DatePicker::make('date_to')
                            ->label(__('payment.bank_statement_lines_relation_manager.date_to')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                // View action removed for now - can be added when proper routes are configured
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading(__('payment.bank_statement_lines_relation_manager.no_bank_statement_lines'))
            ->emptyStateDescription(__('payment.bank_statement_lines_relation_manager.no_bank_statement_lines_description'));
    }
}
