<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\ProjectManagementCluster;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages\CreateTimesheet;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages\EditTimesheet;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages\ListTimesheets;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Schemas\TimesheetForm;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Tables\TimesheetsTable;
use Kezi\ProjectManagement\Models\Timesheet;

class TimesheetResource extends Resource
{
    protected static ?string $cluster = ProjectManagementCluster::class;

    public static function getModelLabel(): string
    {
        return __('projectmanagement::project.timesheet.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projectmanagement::project.timesheet.plural_label');
    }

    protected static ?string $model = Timesheet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return TimesheetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimesheetsTable::configure($table);
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
            'index' => ListTimesheets::route('/'),
            'create' => CreateTimesheet::route('/create'),
            'edit' => EditTimesheet::route('/{record}/edit'),
        ];
    }
}
