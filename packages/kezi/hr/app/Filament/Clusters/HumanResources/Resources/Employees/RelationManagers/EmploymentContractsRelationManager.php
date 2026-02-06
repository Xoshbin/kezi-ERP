<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Schemas\EmploymentContractForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Tables\EmploymentContractsTable;

/**
 * @extends RelationManager<\Kezi\HR\Models\Employee>
 */
class EmploymentContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'employmentContracts';

    protected static ?string $recordTitleAttribute = 'contract_number';

    public function form(Schema $schema): Schema
    {
        return EmploymentContractForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return EmploymentContractsTable::configure($table);
    }
}
