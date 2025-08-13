<?php

namespace App\Filament\Resources\RecurringInvoiceResource\Pages;

use App\Filament\Resources\RecurringInvoiceResource;
use App\Models\RecurringInvoiceTemplate;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewRecurringInvoice extends ViewRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Template Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->label('Template Name'),
                            Infolists\Components\TextEntry::make('targetCompany.name')
                                ->label('Target Company'),
                            Infolists\Components\TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state): string => $state->label())
                                ->color(fn ($state): string => $state->color()),
                        ]),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description'),
                    ]),

                Infolists\Components\Section::make('Scheduling Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('frequency')
                                ->label('Frequency')
                                ->formatStateUsing(fn ($state): string => $state->label())
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('start_date')
                                ->label('Start Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('end_date')
                                ->label('End Date')
                                ->date()
                                ->placeholder('Indefinite'),
                        ]),
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('next_run_date')
                                ->label('Next Run Date')
                                ->date(),
                            Infolists\Components\TextEntry::make('day_of_month')
                                ->label('Day of Month'),
                            Infolists\Components\TextEntry::make('generation_count')
                                ->label('Times Generated')
                                ->numeric(),
                        ]),
                    ]),

                Infolists\Components\Section::make('Financial Configuration')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('incomeAccount.name')
                                ->label('Income Account'),
                            Infolists\Components\TextEntry::make('expenseAccount.name')
                                ->label('Expense Account'),
                        ]),
                        Infolists\Components\TextEntry::make('tax.name')
                            ->label('Default Tax')
                            ->placeholder('No tax'),
                    ]),

                Infolists\Components\Section::make('Line Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('template_data.lines')
                            ->label('Invoice Lines')
                            ->schema([
                                Infolists\Components\Grid::make(4)->schema([
                                    Infolists\Components\TextEntry::make('description')
                                        ->label('Description'),
                                    Infolists\Components\TextEntry::make('quantity')
                                        ->label('Quantity')
                                        ->numeric(),
                                    Infolists\Components\TextEntry::make('unit_price.amount')
                                        ->label('Unit Price')
                                        ->formatStateUsing(function ($state, $record): string {
                                            $currency = $record->currency->code ?? 'USD';
                                            return number_format($state / 100, 2) . ' ' . $currency;
                                        }),
                                    Infolists\Components\TextEntry::make('product_id')
                                        ->label('Product')
                                        ->placeholder('No product'),
                                ]),
                            ]),
                    ]),

                Infolists\Components\Section::make('Generation History')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('last_generated_at')
                                ->label('Last Generated')
                                ->dateTime()
                                ->placeholder('Never generated'),
                            Infolists\Components\TextEntry::make('generation_count')
                                ->label('Total Generated')
                                ->numeric(),
                        ]),
                    ]),

                Infolists\Components\Section::make('Audit Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('createdByUser.name')
                                ->label('Created By'),
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created At')
                                ->dateTime(),
                        ]),
                    ]),
            ]);
    }
}
