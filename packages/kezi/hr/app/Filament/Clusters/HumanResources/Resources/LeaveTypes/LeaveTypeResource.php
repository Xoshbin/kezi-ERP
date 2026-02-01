<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\CreateLeaveType;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\EditLeaveType;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages\ListLeaveTypes;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Schemas\LeaveTypeForm;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Tables\LeaveTypesTable;
use Kezi\HR\Models\LeaveType;

class LeaveTypeResource extends Resource
{
    use Translatable;

    protected static ?string $model = LeaveType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('hr::leave_type.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('hr::leave_type.navigation_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr::leave_type.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('hr::navigation.groups.hr_settings');
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
