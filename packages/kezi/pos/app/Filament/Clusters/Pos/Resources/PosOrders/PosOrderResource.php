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
use Filament\Tables\Table;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Models\PosOrder;

class PosOrderResource extends Resource
{
    protected static ?string $model = PosOrder::class;

    protected static ?string $cluster = PosCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $slug = 'orders';

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session.id')
                    ->label('Session')
                    ->sortable(),
                TextColumn::make('session.user.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
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
                    ->options([
                        'draft' => 'Draft',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('pos_session_id')
                    ->relationship('session', 'id')
                    ->label('Session'),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->schema([
                        TextEntry::make('order_number'),
                        TextEntry::make('ordered_at')->dateTime(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('session.id')->label('Session ID'),
                    ])->columns(2),
                Section::make('Financials')
                    ->schema([
                        TextEntry::make('total_amount')->numeric(),
                        TextEntry::make('total_tax')->numeric(),
                        TextEntry::make('discount_amount')->numeric(),
                        TextEntry::make('currency.code')->label('Currency'),
                    ])->columns(2),
                Section::make('Lines')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                TextEntry::make('product.name')->label('Product'),
                                TextEntry::make('qty')->label('Quantity'),
                                TextEntry::make('unit_price')->numeric()->label('Unit Price'),
                                TextEntry::make('total_amount')->numeric()->label('Total'),
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
