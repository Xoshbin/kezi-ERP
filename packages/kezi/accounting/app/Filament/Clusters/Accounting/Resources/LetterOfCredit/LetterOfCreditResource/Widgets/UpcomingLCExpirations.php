<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LetterOfCredit\LetterOfCreditResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Kezi\Payment\Enums\LetterOfCredit\LCStatus;
use Kezi\Payment\Models\LetterOfCredit;

class UpcomingLCExpirations extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LetterOfCredit::query()
                    ->where('company_id', filament()->getTenant()->id)
                    ->whereIn('status', [LCStatus::Issued, LCStatus::PartiallyUtilized])
                    ->where('expiry_date', '<=', now()->addDays(30))
                    ->where('expiry_date', '>=', now())
                    ->orderBy('expiry_date', 'asc')
            )
            ->heading(__('accounting::lc.upcoming_expirations'))
            ->columns([
                Tables\Columns\TextColumn::make('lc_number')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn (LetterOfCredit $record) => route('filament.kezi.resources.letter-of-credits.edit', $record)),

                Tables\Columns\TextColumn::make('vendor.name')
                    ->label(__('accounting::lc.beneficiary'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (LetterOfCredit $record) => $record->currency->code),

                Tables\Columns\TextColumn::make('balance')
                    ->money(fn (LetterOfCredit $record) => $record->currency->code)
                    ->color('warning'),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->color(fn (LetterOfCredit $record) => match (true) {
                        $record->expiry_date->diffInDays(now()) <= 7 => 'danger',
                        $record->expiry_date->diffInDays(now()) <= 14 => 'warning',
                        default => 'info',
                    })
                    ->icon('heroicon-m-clock')
                    ->description(fn (LetterOfCredit $record) => $record->expiry_date->diffForHumans()),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ]);
    }
}
