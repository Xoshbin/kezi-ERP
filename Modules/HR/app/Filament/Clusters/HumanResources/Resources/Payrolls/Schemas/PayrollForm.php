<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Support\TranslatableHelper;
use Modules\HR\Models\Employee;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr::payroll.sections.basic_information'))
                ->description(__('hr::payroll.sections.basic_information_description'))
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Select::make('employee_id')
                        ->label(__('hr::payroll.fields.employee'))
                        ->relationship('employee', 'first_name')
                        ->getOptionLabelFromRecordUsing(fn (Employee $record): string => $record->full_name.' ('.$record->employee_number.')')
                        ->getSearchResultsUsing(
                            fn (string $search): array => Employee::where('is_active', true)
                                ->where('employment_status', 'active')
                                ->where(function ($query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('employee_number', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Employee $employee): array => [
                                    $employee->id => $employee->full_name.' ('.$employee->employee_number.')',
                                ])
                                ->toArray()
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),

                    Select::make('currency_id')
                        ->label(__('hr::payroll.fields.currency'))
                        ->options(fn () => Currency::all()->mapWithKeys(function ($currency) {
                            return [$currency->id => TranslatableHelper::getLocalizedValue($currency->name)." ({$currency->code})"];
                        }))
                        ->searchable()
                        ->required()
                        ->default(fn () => Currency::where('code', 'IQD')->first()?->id)
                        ->columnSpan(1),

                    DatePicker::make('period_start_date')
                        ->label(__('hr::payroll.fields.period_start_date'))
                        ->required()
                        ->default(now()->startOfMonth())
                        ->columnSpan(1),

                    DatePicker::make('period_end_date')
                        ->label(__('hr::payroll.fields.period_end_date'))
                        ->required()
                        ->default(now()->endOfMonth())
                        ->columnSpan(1),

                    DatePicker::make('pay_date')
                        ->label(__('hr::payroll.fields.pay_date'))
                        ->required()
                        ->default(now()->endOfMonth())
                        ->columnSpan(1),

                    Select::make('pay_frequency')
                        ->label(__('hr::payroll.fields.pay_frequency'))
                        ->options([
                            'monthly' => __('hr::payroll.pay_frequency.monthly'),
                            'bi_weekly' => __('hr::payroll.pay_frequency.bi_weekly'),
                            'weekly' => __('hr::payroll.pay_frequency.weekly'),
                        ])
                        ->required()
                        ->default('monthly')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::payroll.sections.salary_components'))
                ->description(__('hr::payroll.sections.salary_components_description'))
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    MoneyInput::make('base_salary')
                        ->label(__('hr::payroll.fields.base_salary'))
                        ->currencyField('currency_id')
                        ->required()
                        ->columnSpan(2),

                    MoneyInput::make('overtime_amount')
                        ->label(__('hr::payroll.fields.overtime_amount'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('housing_allowance')
                        ->label(__('hr::payroll.fields.housing_allowance'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('transport_allowance')
                        ->label(__('hr::payroll.fields.transport_allowance'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('meal_allowance')
                        ->label(__('hr::payroll.fields.meal_allowance'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('other_allowances')
                        ->label(__('hr::payroll.fields.other_allowances'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('bonus')
                        ->label(__('hr::payroll.fields.bonus'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('commission')
                        ->label(__('hr::payroll.fields.commission'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::payroll.sections.deductions'))
                ->description(__('hr::payroll.sections.deductions_description'))
                ->icon('heroicon-o-minus-circle')
                ->schema([
                    MoneyInput::make('income_tax')
                        ->label(__('hr::payroll.fields.income_tax'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('social_security')
                        ->label(__('hr::payroll.fields.social_security'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('health_insurance')
                        ->label(__('hr::payroll.fields.health_insurance'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('pension_contribution')
                        ->label(__('hr::payroll.fields.pension_contribution'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(1),

                    MoneyInput::make('other_deductions')
                        ->label(__('hr::payroll.fields.other_deductions'))
                        ->currencyField('currency_id')
                        ->default(0)
                        ->columnSpan(2),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::payroll.sections.notes'))
                ->description(__('hr::payroll.sections.notes_description'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    Textarea::make('notes')
                        ->label(__('hr::payroll.fields.notes'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
