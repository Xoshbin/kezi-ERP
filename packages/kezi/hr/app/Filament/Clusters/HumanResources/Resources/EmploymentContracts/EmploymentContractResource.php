<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kezi\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\CreateEmploymentContract;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\EditEmploymentContract;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages\ListEmploymentContracts;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Schemas\EmploymentContractForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Tables\EmploymentContractsTable;
use Kezi\HR\Models\EmploymentContract;

class EmploymentContractResource extends Resource
{
    protected static ?string $model = EmploymentContract::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'contract_number';

    public static function getNavigationLabel(): string
    {
        return __('hr::employment_contract.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('hr::employment_contract.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::employment_contract.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EmploymentContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmploymentContractsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmploymentContracts::route('/'),
            'create' => CreateEmploymentContract::route('/create'),
            'edit' => EditEmploymentContract::route('/{record}/edit'),
        ];
    }
}
