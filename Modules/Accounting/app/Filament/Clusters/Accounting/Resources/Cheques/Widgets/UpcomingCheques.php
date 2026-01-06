<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Payment\Models\Cheque;
use Modules\Payment\Services\Cheques\ChequeMaturityService;

class UpcomingCheques extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                app(ChequeMaturityService::class)->getUpcomingMaturitiesQuery(days: 7)
            )
            ->columns([
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->label('Due Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cheque_number')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Party'),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Cheque $record) => $record->currency->code),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Cheque $record) => \Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
