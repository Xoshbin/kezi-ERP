<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Employees;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\HR\Filament\Clusters\HumanResources\HumanResourcesCluster;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\CreateEmployee;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\EditEmployee;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages\ListEmployees;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Schemas\EmployeeForm;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Tables\EmployeesTable;
use Modules\HR\Models\Employee;

class EmployeeResource extends Resource
{
    // use Translatable;

    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'full_name';

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'employee_number'];
    }

    public static function getNavigationLabel(): string
    {
        return __('hr::employee.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('hr::employee.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::employee.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EmploymentContractsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Employee>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
