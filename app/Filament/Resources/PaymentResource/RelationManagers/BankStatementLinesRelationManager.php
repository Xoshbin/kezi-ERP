<?php

namespace App\Filament\Resources\PaymentResource\RelationManagers;

use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\BankStatementLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BankStatementLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'bankStatementLines';

    protected static ?string $recordTitleAttribute = 'description';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('payment.bank_statement_lines_relation_manager.title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('payment.bank_statement_lines_relation_manager.statement_line_details'))
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label(__('payment.bank_statement_lines_relation_manager.date'))
                            ->required()
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('payment.bank_statement_lines_relation_manager.description'))
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('partner_name')
                            ->label(__('payment.bank_statement_lines_relation_manager.partner_name'))
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('payment.bank_statement_lines_relation_manager.amount'))
                            ->disabled()
                            ->numeric(),

                        Forms\Components\Toggle::make('is_reconciled')
                            ->label(__('payment.bank_statement_lines_relation_manager.is_reconciled'))
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('payment.bank_statement_lines_relation_manager.bank_statement_details'))
                    ->schema([
                        Forms\Components\Select::make('bankStatement.id')
                            ->label(__('payment.bank_statement_lines_relation_manager.bank_statement'))
                            ->relationship('bankStatement', 'name')
                            ->disabled(),

                        Forms\Components\DatePicker::make('bankStatement.statement_date')
                            ->label(__('payment.bank_statement_lines_relation_manager.statement_date'))
                            ->disabled(),

                        Forms\Components\TextInput::make('bankStatement.reference')
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
                Tables\Columns\TextColumn::make('date')
                    ->label(__('payment.bank_statement_lines_relation_manager.date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('payment.bank_statement_lines_relation_manager.description'))
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('partner_name')
                    ->label(__('payment.bank_statement_lines_relation_manager.partner_name'))
                    ->searchable()
                    ->toggleable(),

                MoneyColumn::make('amount')
                    ->label(__('payment.bank_statement_lines_relation_manager.amount'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_reconciled')
                    ->label(__('payment.bank_statement_lines_relation_manager.reconciliation_status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('bankStatement.name')
                    ->label(__('payment.bank_statement_lines_relation_manager.bank_statement'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bankStatement.statement_date')
                    ->label(__('payment.bank_statement_lines_relation_manager.statement_date'))
                    ->date()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bankStatement.reference')
                    ->label(__('payment.bank_statement_lines_relation_manager.statement_reference'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('payment.bank_statement_lines_relation_manager.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_reconciled')
                    ->label(__('payment.bank_statement_lines_relation_manager.filter_reconciliation_status'))
                    ->placeholder(__('payment.bank_statement_lines_relation_manager.filter_all'))
                    ->trueLabel(__('payment.bank_statement_lines_relation_manager.filter_reconciled'))
                    ->falseLabel(__('payment.bank_statement_lines_relation_manager.filter_not_reconciled')),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label(__('payment.bank_statement_lines_relation_manager.date_from')),
                        Forms\Components\DatePicker::make('date_to')
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
            ->actions([
                // View action removed for now - can be added when proper routes are configured
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading(__('payment.bank_statement_lines_relation_manager.no_bank_statement_lines'))
            ->emptyStateDescription(__('payment.bank_statement_lines_relation_manager.no_bank_statement_lines_description'));
    }
}
