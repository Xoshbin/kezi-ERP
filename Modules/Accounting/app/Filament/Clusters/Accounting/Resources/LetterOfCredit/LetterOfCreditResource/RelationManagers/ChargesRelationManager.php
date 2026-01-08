<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Payment\Enums\LetterOfCredit\LCChargeType;

class ChargesRelationManager extends RelationManager
{
    protected static string $relationship = 'charges';

    protected static ?string $title = 'Bank Charges';

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
                    ->label('Expense Account')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('charge_type')
                    ->options(LCChargeType::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->form([
                        Forms\Components\Select::make('charge_type')
                            ->options(LCChargeType::class)
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('IQD'),

                        Forms\Components\Select::make('account_id')
                            ->relationship('account', 'name')
                            ->required()
                            ->searchable()
                            ->label('Expense Account'),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
