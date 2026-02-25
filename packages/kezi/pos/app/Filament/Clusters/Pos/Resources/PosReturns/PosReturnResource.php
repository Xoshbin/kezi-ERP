<?php

namespace Kezi\Pos\Filament\Clusters\Pos\Resources\PosReturns;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Filament\Clusters\Pos\PosCluster;
use Kezi\Pos\Models\PosReturn;

class PosReturnResource extends Resource
{
    protected static ?string $model = PosReturn::class;

    protected static ?string $cluster = PosCluster::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::ArrowUturnLeft;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('pos::pos_return.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('pos::pos_return.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('pos::pos_return.plural_label');
    }

    protected static ?string $slug = 'returns';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->label(__('pos::pos_return.return_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('originalOrder.order_number')
                    ->label(__('pos::pos_return.original_order'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('requestedBy.name')
                    ->label(__('pos::pos_return.requested_by'))
                    ->sortable(),

                TextColumn::make('return_date')
                    ->label(__('pos::pos_return.return_date'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('refund_amount')
                    ->label(__('pos::pos_return.refund_amount'))
                    ->formatStateUsing(fn ($state) => $state?->__toString())
                    ->sortable(),

                TextColumn::make('refund_method')
                    ->label(__('pos::pos_return.refund_method'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label(__('pos::pos_return.status.label'))
                    ->badge()
                    ->color(fn (PosReturnStatus $state): string => $state->color()),

                TextColumn::make('approvedBy.name')
                    ->label(__('pos::pos_return.approved_by'))
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('return_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('pos::pos_return.status.label'))
                    ->options(
                        collect(PosReturnStatus::cases())
                            ->mapWithKeys(fn (PosReturnStatus $s) => [$s->value => $s->label()])
                            ->toArray()
                    ),
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
                Section::make(__('pos::pos_return.section.details'))
                    ->schema([
                        TextEntry::make('return_number')
                            ->label(__('pos::pos_return.return_number'))
                            ->copyable(),
                        TextEntry::make('originalOrder.order_number')
                            ->label(__('pos::pos_return.original_order')),
                        TextEntry::make('return_date')
                            ->label(__('pos::pos_return.return_date'))
                            ->dateTime(),
                        TextEntry::make('status')
                            ->label(__('pos::pos_return.status.label'))
                            ->badge()
                            ->color(fn (PosReturnStatus $state): string => $state->color()),
                        TextEntry::make('return_reason')
                            ->label(__('pos::pos_return.return_reason'))
                            ->placeholder('—'),
                        TextEntry::make('return_notes')
                            ->label(__('pos::pos_return.return_notes'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('pos::pos_return.section.financials'))
                    ->schema([
                        TextEntry::make('refund_amount')
                            ->label(__('pos::pos_return.refund_amount'))
                            ->formatStateUsing(fn ($state) => $state?->__toString()),
                        TextEntry::make('restocking_fee')
                            ->label(__('pos::pos_return.restocking_fee'))
                            ->formatStateUsing(fn ($state) => $state?->__toString()),
                        TextEntry::make('refund_method')
                            ->label(__('pos::pos_return.refund_method'))
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('currency.code')
                            ->label(__('pos::pos_return.currency')),
                    ])->columns(2),

                Section::make(__('pos::pos_return.section.people'))
                    ->schema([
                        TextEntry::make('requestedBy.name')
                            ->label(__('pos::pos_return.requested_by')),
                        TextEntry::make('approvedBy.name')
                            ->label(__('pos::pos_return.approved_by'))
                            ->placeholder('—'),
                        TextEntry::make('approved_at')
                            ->label(__('pos::pos_return.approved_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('session.id')
                            ->label(__('pos::pos_return.session')),
                    ])->columns(2),

                Section::make(__('pos::pos_return.section.lines'))
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('pos::pos_return.product')),
                                TextEntry::make('quantity_returned')
                                    ->label(__('pos::pos_return.quantity_returned'))
                                    ->numeric(),
                                TextEntry::make('unit_price')
                                    ->label(__('pos::pos_return.unit_price'))
                                    ->formatStateUsing(fn ($state) => $state?->__toString()),
                                TextEntry::make('refund_amount')
                                    ->label(__('pos::pos_return.line_refund_amount'))
                                    ->formatStateUsing(fn ($state) => $state?->__toString()),
                                TextEntry::make('item_condition')
                                    ->label(__('pos::pos_return.item_condition'))
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'damaged', 'defective' => 'danger',
                                        'opened' => 'warning',
                                        default => 'success',
                                    })
                                    ->placeholder('—'),
                                TextEntry::make('restock')
                                    ->label(__('pos::pos_return.restock'))
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? __('pos::pos_return.restock_yes') : __('pos::pos_return.restock_no'))
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                            ])->columns(3),
                    ]),

                Section::make(__('pos::pos_return.section.accounting'))
                    ->schema([
                        TextEntry::make('creditNote.reference')
                            ->label(__('pos::pos_return.credit_note'))
                            ->placeholder('—'),
                        TextEntry::make('creditNote.status')
                            ->label(__('pos::pos_return.credit_note_status'))
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('paymentReversal.reference')
                            ->label(__('pos::pos_return.payment_reversal'))
                            ->placeholder('—'),
                        TextEntry::make('paymentReversal.status')
                            ->label(__('pos::pos_return.payment_reversal_status'))
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('stockMove.reference')
                            ->label(__('pos::pos_return.stock_move'))
                            ->placeholder('—'),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosReturns::route('/'),
            'view' => Pages\ViewPosReturn::route('/{record}'),
        ];
    }
}
