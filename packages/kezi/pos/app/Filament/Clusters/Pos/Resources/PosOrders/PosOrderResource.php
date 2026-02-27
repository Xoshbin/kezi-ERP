<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosOrders;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Kezi\Pos\Enums\PosOrderStatus;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Models\PosOrder;

class PosOrderResource extends Resource
{
    protected static ?string $model = PosOrder::class;

    protected static ?string $cluster = PosCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getModelLabel(): string
    {
        return __('pos::pos_order.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pos::pos_order.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pos::pos_order.plural_label');
    }

    protected static ?string $slug = 'orders';

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session.id')
                    ->label(__('pos::pos_order.session'))
                    ->sortable(),
                TextColumn::make('session.user.name')
                    ->label(__('pos::pos_order.user'))
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_tax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PosOrderStatus::class),
                SelectFilter::make('pos_session_id')
                    ->relationship('session', 'id')
                    ->label(__('pos::pos_order.session')),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('pos::pos_order.order_details'))
                    ->schema([
                        TextEntry::make('order_number')->label(__('pos::pos_order.order_number')),
                        TextEntry::make('ordered_at')->label(__('pos::pos_order.ordered_at'))->dateTime(),
                        TextEntry::make('status')->label(__('pos::pos_order.status'))->badge(),
                        TextEntry::make('session.id')->label(__('pos::pos_order.session_id')),
                    ])->columns(2),
                Section::make(__('pos::pos_order.financials'))
                    ->schema([
                        TextEntry::make('total_amount')->label(__('pos::pos_order.total_amount'))->numeric(),
                        TextEntry::make('total_tax')->label(__('pos::pos_order.total_tax'))->numeric(),
                        TextEntry::make('discount_amount')->label(__('pos::pos_order.discount_amount'))->numeric(),
                        TextEntry::make('currency.code')->label(__('pos::pos_order.currency')),
                    ])->columns(2),
                Section::make(__('pos::pos_order.lines'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                TextEntry::make('product.name')->label(__('pos::pos_order.product')),
                                TextEntry::make('qty')->label(__('pos::pos_order.quantity')),
                                TextEntry::make('unit_price')->label(__('pos::pos_order.unit_price'))->numeric(),
                                TextEntry::make('total_amount')->label(__('pos::pos_order.total'))->numeric(),
                            ])->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosOrders::route('/'),
            'view' => Pages\ViewPosOrder::route('/{record}'),
        ];
    }
}
