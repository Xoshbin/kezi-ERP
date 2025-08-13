<?php

namespace App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource\Pages;

use App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource;
use App\Models\StockMove;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInterCompanyStockTransfer extends ViewRecord
{
    protected static string $resource = InterCompanyStockTransferResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transfer Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('reference')
                                ->label('Reference')
                                ->copyable(),
                            Infolists\Components\TextEntry::make('move_date')
                                ->label('Transfer Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state): string => $state->label())
                                ->color(fn ($state): string => match ($state) {
                                    \App\Enums\Inventory\StockMoveStatus::Done => 'success',
                                    \App\Enums\Inventory\StockMoveStatus::Confirmed => 'warning',
                                    default => 'gray',
                                }),
                        ]),
                    ]),

                Infolists\Components\Section::make('Company Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('company.name')
                                ->label('Company'),
                            Infolists\Components\TextEntry::make('move_type')
                                ->label('Transfer Type')
                                ->badge()
                                ->formatStateUsing(fn ($state): string => $state->label())
                                ->color(fn ($state): string => match ($state) {
                                    \App\Enums\Inventory\StockMoveType::Incoming => 'success',
                                    \App\Enums\Inventory\StockMoveType::Outgoing => 'danger',
                                    default => 'gray',
                                }),
                        ]),
                    ]),

                Infolists\Components\Section::make('Product & Location Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('product.name')
                                ->label('Product'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('Quantity')
                                ->numeric(decimalPlaces: 4),
                        ]),
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('fromLocation.name')
                                ->label('From Location'),
                            Infolists\Components\TextEntry::make('toLocation.name')
                                ->label('To Location'),
                        ]),
                    ]),

                Infolists\Components\Section::make('Related Transfer')
                    ->schema([
                        Infolists\Components\TextEntry::make('related_transfer')
                            ->label('Related Transfer')
                            ->formatStateUsing(function (StockMove $record): string {
                                // Find the related transfer
                                if (str_starts_with($record->reference ?? '', 'IC-TRANSFER-')) {
                                    // This is a receipt, find the original delivery
                                    $sourceId = str_replace('IC-TRANSFER-', '', $record->reference);
                                    $sourceMove = StockMove::find($sourceId);
                                    if ($sourceMove) {
                                        return "Delivery #{$sourceMove->id} in {$sourceMove->company->name}";
                                    }
                                } else {
                                    // This is a delivery, find the corresponding receipt
                                    $receiptMove = StockMove::where('reference', "IC-TRANSFER-{$record->id}")->first();
                                    if ($receiptMove) {
                                        return "Receipt #{$receiptMove->id} in {$receiptMove->company->name}";
                                    }
                                }
                                return 'No related transfer found';
                            }),
                    ])
                    ->visible(fn (StockMove $record): bool => 
                        str_starts_with($record->reference ?? '', 'IC-TRANSFER-') || 
                        StockMove::where('reference', "IC-TRANSFER-{$record->id}")->exists()
                    ),

                Infolists\Components\Section::make('Audit Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('createdByUser.name')
                                ->label('Created By'),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created At')
                                ->dateTime(),
                        ]),
                        Infolists\Components\TextEntry::make('source_type')
                            ->label('Source Document')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'Manual Transfer'),
                    ]),
            ]);
    }
}
