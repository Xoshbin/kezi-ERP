<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions;

use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Models\PosSession;

class PosSessionResource extends Resource
{
    protected static ?string $model = PosSession::class;

    protected static ?string $cluster = PosCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $slug = 'sessions';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'opened')->count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('profile.name')
                    ->label('Profile')
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'opened' => 'success',
                        'closed' => 'gray',
                        'closing' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('opening_cash')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('closing_cash')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->state(fn (PosSession $record): float => $record->orders->sum('total_amount') / 100)
                    ->numeric()
                    ->label('Total Revenue'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosSessions::route('/'),
            'view' => Pages\ViewPosSession::route('/{record}'),
        ];
    }
}
