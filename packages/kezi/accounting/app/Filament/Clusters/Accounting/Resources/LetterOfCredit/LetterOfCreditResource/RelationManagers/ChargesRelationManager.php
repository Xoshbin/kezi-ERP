<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Payment\Enums\LetterOfCredit\LCChargeType;

class ChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'charges';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('accounting::lc.bank_charges');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('charge_type')
            ->columns([
                Tables\Columns\TextColumn::make('charge_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('charge_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label(__('accounting::lc.expense_account'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('charge_type')
                    ->options(LCChargeType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form([
                        Forms\Components\Select::make('charge_type')
                            ->options(LCChargeType::class)
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('IQD'),

                        AccountSelectField::make('account_id')
                            ->required()
                            ->label(__('accounting::lc.expense_account')),

                        Forms\Components\DatePicker::make('charge_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                    ])
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        $lc = $livewire->getOwnerRecord();
                        $data['company_id'] = $lc->company_id;
                        $data['currency_id'] = $lc->currency_id;
                        $data['amount_company_currency'] = $data['amount']; // Simplified

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
