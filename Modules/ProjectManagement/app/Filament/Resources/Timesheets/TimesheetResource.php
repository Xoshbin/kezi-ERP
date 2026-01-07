<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\ProjectManagement\Filament\Resources\Timesheets\Pages\CreateTimesheet;
use Modules\ProjectManagement\Filament\Resources\Timesheets\Pages\EditTimesheet;
use Modules\ProjectManagement\Filament\Resources\Timesheets\Pages\ListTimesheets;
use Modules\ProjectManagement\Filament\Resources\Timesheets\Schemas\TimesheetForm;
use Modules\ProjectManagement\Filament\Resources\Timesheets\Tables\TimesheetsTable;
use Modules\ProjectManagement\Models\Timesheet;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
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
