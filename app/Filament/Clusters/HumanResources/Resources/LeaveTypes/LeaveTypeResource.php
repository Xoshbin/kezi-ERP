<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes;

use App\Filament\Clusters\HumanResources\HumanResourcesCluster;
use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\CreateLeaveType;
use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\EditLeaveType;
use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\ListLeaveTypes;
use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Schemas\LeaveTypeForm;
use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Tables\LeaveTypesTable;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class LeaveTypeResource extends Resource
{
    use Translatable;

    protected static ?string $model = LeaveType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $cluster = HumanResourcesCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('leave_type.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('leave_type.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('leave_type.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {
        return LeaveTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveTypesTable::configure($table);
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
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }
}
