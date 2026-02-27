<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosSessions;

use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Kezi\Pos\Enums\PosSessionStatus;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Models\PosSession;

class PosSessionResource extends Resource
{
    protected static ?string $model = PosSession::class;

    protected static ?string $cluster = PosCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public static function getModelLabel(): string
    {
        return __('pos::pos_session.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pos::pos_session.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pos::pos_session.plural_label');
    }

    protected static ?string $slug = 'sessions';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', PosSessionStatus::Opened)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('pos::pos_session.user'))
                    ->sortable(),
                TextColumn::make('profile.name')
                    ->label(__('pos::pos_session.profile'))
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('opening_cash')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('closing_cash')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->state(fn (PosSession $record): float => $record->orders()->sum('total_amount') / 100)
                    ->numeric()
                    ->label(__('pos::pos_session.total_revenue')),
            ])
            ->filters([
                TrashedFilter::make(),
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
