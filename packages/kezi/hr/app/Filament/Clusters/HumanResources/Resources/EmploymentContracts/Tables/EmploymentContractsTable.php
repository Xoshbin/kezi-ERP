<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kezi\HR\Models\EmploymentContract;

class EmploymentContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract_number')
                    ->label(__('hr::employment_contract.fields.contract_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.full_name') // Using accessor if exists, or chain it
                    ->label(__('hr::employment_contract.fields.employee'))
                    ->getStateUsing(fn (EmploymentContract $record) => $record->employee->first_name.' '.$record->employee->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('contract_type')
                    ->label(__('hr::employment_contract.fields.contract_type'))
                    ->formatStateUsing(fn (string $state): string => __("hr::employment_contract.types.{$state}"))
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('hr::employment_contract.fields.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('hr::employment_contract.fields.end_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('base_salary')
                    ->label(__('hr::employment_contract.fields.base_salary'))
                    ->money(fn (EmploymentContract $record) => $record->currency->code)
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('hr::employment_contract.fields.status'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): \Kezi\HR\Models\EmploymentContract {
                        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;
                        $currencyCode = \Kezi\Foundation\Models\Currency::findOrFail($data['currency_id'])->code;

                        return app(\Kezi\HR\Actions\EmploymentContracts\CreateEmploymentContractAction::class)
                            ->execute(\Kezi\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (\Kezi\HR\Models\EmploymentContract $record, array $data): \Kezi\HR\Models\EmploymentContract {
                        $currencyCode = $record->currency->code;

                        return app(\Kezi\HR\Actions\EmploymentContracts\UpdateEmploymentContractAction::class)
                            ->execute($record, \Kezi\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
