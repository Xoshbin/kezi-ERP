<?php

namespace App\Filament\Clusters\HumanResources\Resources\Employees;

use App\Filament\Clusters\HumanResources\HumanResourcesCluster;
use App\Filament\Clusters\HumanResources\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Clusters\HumanResources\Resources\Employees\Pages\EditEmployee;
use App\Filament\Clusters\HumanResources\Resources\Employees\Pages\ListEmployees;
use App\Filament\Clusters\HumanResources\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Clusters\HumanResources\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class EmployeeResource extends Resource
{
    use Translatable;

    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'full_name';

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
            //
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
