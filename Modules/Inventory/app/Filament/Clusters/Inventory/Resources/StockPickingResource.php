<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources;

use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\CreateStockPicking;
use App\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\EditStockPicking;
use App\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ListStockPickings;
use App\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages\ViewStockPicking;
use App\Models\StockPicking;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;


class StockPickingResource extends Resource
{
    protected static ?string $model = StockPicking::class;

    protected static ?string $cluster = InventoryCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('Stock Pickings');
    }

    public static function getModelLabel(): string
    {
        return __('Stock Picking');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Stock Pickings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Basic Information'))
                ->schema([
                    Forms\Components\TextInput::make('reference')
                        ->label(__('Reference'))
                        ->required()
                        ->maxLength(255)
                        ->default(fn() => 'SP-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT)),

                    Forms\Components\Select::make('type')
                        ->label(__('Type'))
                        ->options(StockPickingType::class)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('state')
                        ->label(__('State'))
                        ->options(StockPickingState::class)
                        ->default(StockPickingState::Draft)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('partner_id')
                        ->label(__('Partner'))
                        ->relationship('partner', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\DateTimePicker::make('scheduled_date')
                        ->label(__('Scheduled Date'))
                        ->default(now())
                        ->required(),

                    Forms\Components\TextInput::make('origin')
                        ->label(__('Origin'))
                        ->maxLength(255),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(StockPickingType $state): string => match ($state) {
                        StockPickingType::Receipt => 'success',
                        StockPickingType::Delivery => 'danger',
                        StockPickingType::Internal => 'info',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn(StockPickingState $state): string => match ($state) {
                        StockPickingState::Draft => 'gray',
                        StockPickingState::Confirmed => 'warning',
                        StockPickingState::Assigned => 'info',
                        StockPickingState::Done => 'success',
                        StockPickingState::Cancelled => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('origin')
                    ->label('Origin')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stockMoves')
                    ->label('Moves')
                    ->counts('stockMoves')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(StockPickingType::class),

                Tables\Filters\SelectFilter::make('state')
                    ->label('State')
                    ->options(StockPickingState::class),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                \Filament\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(StockPicking $record): bool => $record->state === StockPickingState::Draft),
                \Filament\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn(StockPicking $record): bool => $record->state === StockPickingState::Draft),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockPickings::route('/'),
            'create' => CreateStockPicking::route('/create'),
            'view' => ViewStockPicking::route('/{record}'),
            'edit' => EditStockPicking::route('/{record}/edit'),
        ];
    }
}
