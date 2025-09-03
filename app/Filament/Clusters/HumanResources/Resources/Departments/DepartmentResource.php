<?php

namespace App\Filament\Clusters\HumanResources\Resources\Departments;

use App\Filament\Clusters\HumanResources\HumanResourcesCluster;
use App\Filament\Clusters\HumanResources\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Clusters\HumanResources\Resources\Departments\Pages\EditDepartment;
use App\Filament\Clusters\HumanResources\Resources\Departments\Pages\ListDepartments;
use App\Filament\Clusters\HumanResources\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Clusters\HumanResources\Resources\Departments\Tables\DepartmentsTable;
use App\Models\Department;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class DepartmentResource extends Resource
{
    use Translatable;

    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('department.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('department.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('department.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return DepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentsTable::configure($table);
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
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
