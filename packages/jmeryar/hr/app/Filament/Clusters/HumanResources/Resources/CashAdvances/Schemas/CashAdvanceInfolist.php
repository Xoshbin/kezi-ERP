<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\CashAdvances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashAdvanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('hr::cash_advance.sections.advance_details'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('advance_number')
                                    ->label(__('hr::cash_advance.advance_number'))
                                    ->weight('bold'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('employee.full_name')
                                    ->label(__('hr::cash_advance.employee')),
                                TextEntry::make('company.name')
                                    ->label(__('hr::cash_advance.company')),
                                TextEntry::make('requested_amount')
                                    ->money(fn ($record) => $record->currency->code),
                                TextEntry::make('approved_amount')
                                    ->money(fn ($record) => $record->currency->code)
                                    ->placeholder(__('hr::cash_advance.placeholders.na')),
                                TextEntry::make('purpose')
                                    ->columnSpanFull(),
                                TextEntry::make('notes')
                                    ->columnSpanFull()
                                    ->placeholder(__('hr::cash_advance.placeholders.no_notes')),
                            ]),
                    ]),
                Section::make(__('hr::cash_advance.sections.dates_approvals'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('requested_at')
                                    ->dateTime(),
                                TextEntry::make('expected_return_date')
                                    ->date(),
                                TextEntry::make('approved_at')
                                    ->dateTime()
                                    ->placeholder(__('hr::cash_advance.placeholders.pending_approval')),
                                TextEntry::make('approvedBy.name')
                                    ->label(__('hr::cash_advance.approved_by'))
                                    ->placeholder(__('hr::cash_advance.placeholders.na')),
                                TextEntry::make('disbursed_at')
                                    ->dateTime()
                                    ->placeholder(__('hr::cash_advance.placeholders.not_disbursed')),
                                TextEntry::make('disbursedBy.name')
                                    ->label(__('hr::cash_advance.disbursed_by'))
                                    ->placeholder(__('hr::cash_advance.placeholders.na')),
                                TextEntry::make('settled_at')
                                    ->dateTime()
                                    ->placeholder(__('hr::cash_advance.placeholders.not_settled')),
                            ]),
                    ]),
            ]);
    }
}
