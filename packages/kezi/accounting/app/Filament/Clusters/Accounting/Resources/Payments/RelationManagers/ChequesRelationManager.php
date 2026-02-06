<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Payments\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * @extends RelationManager<\Kezi\Payment\Models\Payment>
 */
class ChequesRelationManager extends RelationManager
{
    protected static string $relationship = 'cheques';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('cheque_number')
            ->columns([
                Tables\Columns\TextColumn::make('cheque_number'),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency->code),
                Tables\Columns\TextColumn::make('due_date')
                    ->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Usually we don't create cheques directly from Payment,
                // but maybe we want to link existing ones?
                // For now, read-only list is fine or maybe allowed to create if it makes sense.
                // Given the requirement "Add Cheque RelationManager to Payment Resource",
                // it implies viewing cheques associated with a payment (like a batch payment).
            ])
            ->actions([
                EditAction::make()
                    ->url(fn ($record) => \Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
