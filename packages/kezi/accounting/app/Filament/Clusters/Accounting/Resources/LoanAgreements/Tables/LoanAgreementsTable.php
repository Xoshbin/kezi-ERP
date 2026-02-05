<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LoanAgreements\Tables;

use \Filament\Actions\BulkActionGroup;
use \Filament\Actions\DeleteBulkAction;
use \Filament\Actions\EditAction;
use \Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanAgreementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label(__('accounting::common.company'))
                    ->searchable(),
                TextColumn::make('partner.name')
                    ->label(__('accounting::loan.form.partner'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('accounting::loan.form.name'))
                    ->searchable(),
                TextColumn::make('loan_date')
                    ->label(__('accounting::loan.form.loan_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(__('accounting::loan.form.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('maturity_date')
                    ->label(__('accounting::loan.form.maturity_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('duration_months')
                    ->label(__('accounting::loan.form.duration_months'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency.name')
                    ->label(__('accounting::loan.form.currency'))
                    ->searchable(),
                TextColumn::make('principal_amount')
                    ->label(__('accounting::loan.form.principal_amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('outstanding_principal')
                    ->label(__('accounting::loan.form.outstanding_principal'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('loan_type')
                    ->label(__('accounting::loan.form.loan_type'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('accounting::loan.form.status'))
                    ->searchable(),
                TextColumn::make('schedule_method')
                    ->label(__('accounting::loan.form.schedule_method'))
                    ->searchable(),
                TextColumn::make('interest_rate')
                    ->label(__('accounting::loan.form.interest_rate'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('eir_enabled')
                    ->label(__('accounting::loan.form.eir_enabled'))
                    ->boolean(),
                TextColumn::make('eir_rate')
                    ->label(__('accounting::loan.form.eir_rate'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('accounting::common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('accounting::common.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
