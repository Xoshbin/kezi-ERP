<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section
                Section::make(__('hr::payroll.sections.basic_information'))
                    ->description(__('hr::payroll.sections.basic_information_description'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payroll_number')
                                    ->label(__('hr::payroll.fields.payroll_number'))
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('status')
                                    ->label(__('hr::payroll.fields.status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'processed' => 'warning',
                                        'paid' => 'success',
                                        default => 'gray',
                                    }),

                                TextEntry::make('employee.full_name')
                                    ->label(__('hr::payroll.fields.employee'))
                                    ->icon('heroicon-o-user'),
                            ]),
                    ]),

                // Period Information
                Section::make(__('hr::payroll.sections.period_payment_info'))
                    ->description(__('hr::payroll.sections.period_payment_info_description'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('period_start_date')
                                    ->label(__('hr::payroll.fields.period_start_date'))
                                    ->date(),

                                TextEntry::make('period_end_date')
                                    ->label(__('hr::payroll.fields.period_end_date'))
                                    ->date(),

                                TextEntry::make('pay_date')
                                    ->label(__('hr::payroll.fields.pay_date'))
                                    ->date(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pay_frequency')
                                    ->label(__('hr::payroll.fields.pay_frequency'))
                                    ->badge(),

                                TextEntry::make('currency.name')
                                    ->label(__('hr::payroll.fields.currency'))
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible(),

                // Salary Components
                Section::make(__('hr::payroll.sections.salary_components'))
                    ->description(__('hr::payroll.sections.salary_components_description'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('base_salary')
                                    ->label(__('hr::payroll.fields.base_salary'))
                                    ->numeric()
                                    ->color('success'),

                                TextEntry::make('housing_allowance')
                                    ->label(__('hr::payroll.fields.housing_allowance'))
                                    ->numeric(),

                                TextEntry::make('transport_allowance')
                                    ->label(__('hr::payroll.fields.transport_allowance'))
                                    ->numeric(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('meal_allowance')
                                    ->label(__('hr::payroll.fields.meal_allowance'))
                                    ->numeric(),

                                TextEntry::make('bonus')
                                    ->label(__('hr::payroll.fields.bonus'))
                                    ->numeric(),

                                TextEntry::make('overtime_amount')
                                    ->label(__('hr::payroll.fields.overtime_amount'))
                                    ->numeric(),
                            ]),
                    ])
                    ->collapsible(),

                // Deductions
                Section::make(__('hr::payroll.sections.deductions'))
                    ->description(__('hr::payroll.sections.deductions_description'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('income_tax')
                                    ->label(__('hr::payroll.fields.income_tax'))
                                    ->numeric()
                                    ->color('danger'),

                                TextEntry::make('social_security')
                                    ->label(__('hr::payroll.fields.social_security'))
                                    ->numeric()
                                    ->color('danger'),

                                TextEntry::make('health_insurance')
                                    ->label(__('hr::payroll.fields.health_insurance'))
                                    ->numeric()
                                    ->color('danger'),
                            ]),
                    ])
                    ->collapsible(),

                // Summary
                Section::make(__('hr::payroll.sections.summary'))
                    ->description(__('hr::payroll.sections.summary_description'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('gross_salary')
                                    ->label(__('hr::payroll.fields.gross_salary'))
                                    ->numeric()
                                    ->color('success')
                                    ->size('lg'),

                                TextEntry::make('total_deductions')
                                    ->label(__('hr::payroll.fields.total_deductions'))
                                    ->numeric()
                                    ->color('danger')
                                    ->size('lg'),

                                TextEntry::make('net_salary')
                                    ->label(__('hr::payroll.fields.net_salary'))
                                    ->numeric()
                                    ->color('primary')
                                    ->size('xl'),
                            ]),
                    ]),

                // System Information
                Section::make(__('hr::payroll.sections.system_info'))
                    ->description(__('hr::payroll.sections.system_info_description'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('hr::payroll.fields.created_at'))
                                    ->dateTime(),

                                TextEntry::make('updated_at')
                                    ->label(__('hr::payroll.fields.updated_at'))
                                    ->dateTime(),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
